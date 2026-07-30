<?php

namespace App\Jobs;

use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Enums\Status;
use App\Events\SeriesUpdated;
use App\Integrations\JellyfinCLient;
use App\Integrations\Sonarr\Dto\importFile;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use App\Services\SeriesStatsBroadcaster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Первое звено chain: импорт в Sonarr (move), ожидание завершения команды,
 * затем Jellyfin rescan и синхронизация состояния. Удаление из qBittorrent — в следующей джобе цепочки.
 *
 * Сначала получаем из Sonarr данные по пути (GET manualimport с seriesId), формируем тело команды ManualImport
 * (path, seriesId, seasonNumber, episodeIds, quality, languages, indexerFlags, releaseType) и отправляем
 * POST /api/v3/command. Если подходящих файлов нет — fallback на DownloadedEpisodesScan.
 */
class ImportDownloadToSonarrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Максимум ожидания завершения команды Sonarr (секунды) */
    private const SONARR_COMMAND_TIMEOUT = 300;

    public $timeout = 360;

    /** Интервал опроса статуса (секунды) */
    private const POLL_INTERVAL = 3;

    /**
     * Create a new job instance.
     *
     * @param  int  $seriesId  Идентификатор сериала
     * @param  array  $files  Список метаданных загруженных файлов
     */
    public function __construct(
        public int $seriesId,
        public array $files,
    ) {}

    /**
     * Execute the job.
     *
     * @param  SonarrClient  $sonarrClient  Экземпляр клиента Sonarr
     * @param  JellyfinCLient  $jellyfinClient  Экземпляр клиента Jellyfin
     */
    public function handle(SonarrClient $sonarrClient, JellyfinCLient $jellyfinClient): void
    {
        $series = Series::find($this->seriesId);
        if (! $series) {
            return;
        }

        $logger = app(AniarrLogger::class);
        $path = $series->active_download_path;

        $logger->info('[ImportSonarr] Джоба стартовала', [
            'active_torrent_hash' => $series->active_torrent_hash,
            'active_download_path_raw' => $path,
            'path_is_empty' => $path === null || $path === '',
        ]);

        $series->update([
            'status' => Status::PROCESSING_SONARR,
            'last_updated' => now(),
        ]);
        broadcast(new SeriesUpdated($series->fresh()))->toOthers();

        try {
            $sonarrOk = $sonarrClient->testConnection();
            $logger->info('[ImportSonarr] Перед вызовом Sonarr API', [
                'sonarr_test_connection' => $sonarrOk,
                'files_count' => count($this->files),
            ]);

            if (! $sonarrOk) {
                $logger->warning('[ImportSonarr] Sonarr не подключен');

                return;
            }

            $this->parseEpisodesFromFiles();
            $command = $this->tryManualImportCommand($sonarrClient, $series);

            if ($command === null) {
                $logger->warning('[ImportSonarr] Sonarr не вернул command id');

                return;
            }

            // $logger->info('log.jobs.import_sonarr_scan_ok', [], [], $series->id);
            $this->waitForSonarrCommand($sonarrClient, (int) $command['id'], $series->id);

            $sonarrSeries = $sonarrClient->findByTvdbId($series->thetvdb_id);
            if ($sonarrSeries !== null) {
                (new SyncSeriesStateFromSonarrAction)->execute($series->fresh(), $sonarrSeries, $sonarrClient);
            }

            if ($jellyfinClient->testConnection()) {
                $series->update([
                    'status' => Status::SYNCING_JELLYFIN,
                    'last_updated' => now(),
                ]);
                broadcast(new SeriesUpdated($series->fresh()))->toOthers();
                $jellyfinOk = $jellyfinClient->refreshLibrary();
                if ($jellyfinOk) {
                    // $logger->info('log.jobs.import_jellyfin_ok', [], [], $series->id);
                }
            }
        } catch (Throwable $e) {
            $logger->error('log.jobs.import_sonarr_failed', $e);
            $series->update([
                'status' => Status::ERROR,
                'error_message' => 'Import: '.$e->getMessage(),
                'last_updated' => now(),
            ]);
            broadcast(new SeriesUpdated($series->fresh()))->toOthers();
            SeriesStatsBroadcaster::broadcast();
        }
    }

    /**
     * Получаем из Sonarr список файлов по пути (GET manualimport), формируем тело команды ManualImport
     * и отправляем POST /api/v3/command. Возвращает ответ команды (с id) или null.
     *
     * @param  SonarrClient  $sonarrClient  Экземпляр клиента Sonarr
     * @param  Series  $series  Модель сериала
     * @return array|null Ответ команды с ID или null при ошибке
     */
    private function tryManualImportCommand(
        SonarrClient $sonarrClient,
        Series $series,
    ): ?array {
        $sonarrSeries = $sonarrClient->findByTvdbId($series->thetvdb_id);
        $sonarEpisodes = $sonarrClient->getEpisodes($sonarrSeries['id']);

        $commandFiles = [];

        foreach ($sonarEpisodes as $episode) {
            $file = collect($this->files)->filter(function (array $file) use ($episode) {
                return $file['episodeNumber'] === $episode['episodeNumber'];
            })->first() ?? [];

            if (empty($file)) {
                continue;
            }

            $commandFiles[] = new importFile(
                $file['path'],
                $sonarrSeries['id'],
                $episode['seasonNumber'],
                $episode['id'],
            );
        }

        if ($commandFiles === []) {
            app(AniarrLogger::class)->info('[ImportSonarr] Нет файлов с эпизодами, fallback на scan');

            return null;
        }

        $command = $sonarrClient->sendManualImportCommand($commandFiles, 'move');

        app(AniarrLogger::class)->info('[ImportSonarr] Ответ Sonarr ManualImport', [
            'command_id' => $command['id'] ?? null,
            'files_count' => count($commandFiles),
        ]);

        return $command;
    }

    /**
     * Ожидание завершения команды Sonarr до завершения или таймаута.
     *
     * @param  SonarrClient  $sonarrClient  Экземпляр клиента Sonarr
     * @param  int  $commandId  ID команды Sonarr
     * @param  int  $seriesId  Идентификатор сериала (для логирования)
     */
    private function waitForSonarrCommand(SonarrClient $sonarrClient, int $commandId, int $seriesId): void
    {
        $deadline = time() + self::SONARR_COMMAND_TIMEOUT;
        $pollCount = 0;
        while (time() < $deadline) {
            sleep(self::POLL_INTERVAL);
            $pollCount++;
            $cmd = $sonarrClient->getCommand($commandId);
            if ($cmd === null) {
                app(AniarrLogger::class)->debug('[ImportSonarr] Poll command: getCommand вернул null', [
                    'command_id' => $commandId,
                    'poll' => $pollCount,
                ]);

                continue;
            }
            $status = $cmd['status'] ?? '';
            if ($pollCount <= 2 || $status === 'completed' || $status === 'failed') {
                app(AniarrLogger::class)->info('[ImportSonarr] Статус команды Sonarr', [
                    'command_id' => $commandId,
                    'status' => $status,
                    'poll' => $pollCount,
                    'body' => $cmd['body'] ?? null,
                ]);
            }
            if ($status === 'completed' || $status === 'failed') {
                app(AniarrLogger::class)->info('[ImportSonarr] Команда Sonarr завершена', [
                    'command_id' => $commandId,
                    'status' => $status,
                    'full_command_response' => $cmd,
                ]);

                return;
            }
        }
        app(AniarrLogger::class)->warning('[ImportSonarr] Таймаут ожидания команды Sonarr', [
            'command_id' => $commandId,
            'polls' => $pollCount,
        ]);
    }

    /**
     * Извлекает номера эпизодов из путей файлов и добавляет их в метаданные файлов.
     */
    private function parseEpisodesFromFiles(): void
    {
        foreach ($this->files as &$file) {
            preg_match('/\[(\d{2})\]/', $file['path'], $matches);
            if (! isset($matches[1])) {
                continue;
            }

            $episodeNumber = (int) $matches[1];
            if ($episodeNumber > 0) {
                $file['episodeNumber'] = $episodeNumber;
            }
        }
    }
}
