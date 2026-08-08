<?php

namespace App\Jobs;

use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Enums\Status;
use App\Events\SeriesUpdated;
use App\Integrations\JellyfinClient;
use App\Integrations\QBittorrent\Dto\File;
use App\Integrations\Sonarr\Dto\importFile;
use App\Integrations\Sonarr\Dto\SonarrEpisode;
use App\Integrations\Sonarr\Dto\SonarrSeries;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Series;
use App\Models\Torrent;
use App\Services\Logging\AniarrLogger;
use App\Services\SeriesStatsBroadcaster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/**
 * Импорт в Sonarr (move), ожидание завершения команды,
 * затем Jellyfin rescan и синхронизация состояния. После успешного импорта запускает удаление торрента.
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
     * @param  int  $torrentId  Идентификатор сериала
     * @param  array  $files  Список метаданных загруженных файлов
     */
    public function __construct(
        public int $torrentId,
        public array $files,
    ) {}

    /**
     * Execute the job.
     *
     * @param  SonarrClient  $sonarrClient  Экземпляр клиента Sonarr
     * @param  JellyfinClient  $jellyfinClient  Экземпляр клиента Jellyfin
     */
    public function handle(SonarrClient $sonarrClient, JellyfinClient $jellyfinClient): void
    {
        $torrent = Torrent::query()
            ->with('series')
            ->where('id', $this->torrentId)
            ->first();
        if (! $torrent) {
            return;
        }

        $logger = app(AniarrLogger::class);
        $path = $torrent->active_download_path;

        $logger->debug('[Torrent] Джоба стартовала', [
            'active_torrent_hash' => $torrent->active_torrent_hash,
            'active_download_path_raw' => $path,
            'path_is_empty' => $path === null || $path === '',
        ]);

        $torrent->series->update([
            'status' => Status::PROCESSING_SONARR,
            'last_updated' => now(),
        ]);
        broadcast(new SeriesUpdated($torrent->series->fresh()))->toOthers();

        try {
            $sonarrOk = $sonarrClient->testConnection();
            $logger->debug('[Sonarr] Перед вызовом Sonarr API', [
                'sonarr_test_connection' => $sonarrOk,
                'files_count' => count($this->files),
            ]);

            if (! $sonarrOk) {
                $logger->error('[Sonarr] Sonarr не подключен');
                throw new RuntimeException('Sonarr не подключен');
            }

            $this->parseEpisodesFromFiles();
            $command = $this->tryManualImportCommand($sonarrClient, $torrent);

            if (empty($command)) {
                $logger->error('[Sonarr] Sonarr не вернул command id');
                throw new RuntimeException('Sonarr не вернул command id (нет файлов с эпизодами)');
            }

            $commandStatus = $this->waitForSonarrCommand($sonarrClient, (int) $command['id']);

            if ($commandStatus === 'failed') {
                throw new RuntimeException('Команда Sonarr завершилась с ошибкой');
            }

            if ($commandStatus === 'timeout') {
                throw new RuntimeException('Таймаут ожидания команды Sonarr');
            }

            $sonarrSeries = $sonarrClient->findByTvdbId($torrent->series->thetvdb_id);
            if ($sonarrSeries !== null) {
                (new SyncSeriesStateFromSonarrAction)->execute($torrent->series, $sonarrSeries, $sonarrClient);
            }

            if ($jellyfinClient->testConnection()) {
                $torrent->update([
                    'status' => Status::SYNCING_JELLYFIN,
                    'last_updated' => now(),
                ]);
                broadcast(new SeriesUpdated($torrent->series))->toOthers();
                $jellyfinOk = $jellyfinClient->refreshLibrary();
                if ($jellyfinOk) {
                    $logger->info('[Jellyfin] Запустилась синхронизация');
                }
            }

            // После успешного импорта в Sonarr и синхронизации с Jellyfin запускаем удаление торрента
            DeleteTorrentFromQBitJob::dispatch($this->torrentId)->onQueue('downloads');

            $logger->info('[Sonarr] Импорт успешно завершен, запущено удаление торрента');
        } catch (Throwable $e) {
            $logger->exception($e);
            $torrent->series->update([
                'status' => Status::ERROR,
                'error_message' => 'Import: ' . $e->getMessage(),
                'last_updated' => now(),
            ]);
            broadcast(new SeriesUpdated($torrent->series))->toOthers();
            SeriesStatsBroadcaster::broadcast();
            throw $e;
        }
    }

    /**
     * Получаем из Sonarr список файлов по пути (GET manualimport), формируем тело команды ManualImport
     * и отправляем POST /api/v3/command. Возвращает ответ команды (с id) или null.
     *
     * @param  SonarrClient  $sonarrClient  Экземпляр клиента Sonarr
     * @param  Torrent  $torrent  Модель сериала
     * @return array|null Ответ команды с ID или null при ошибке
     */
    private function tryManualImportCommand(
        SonarrClient $sonarrClient,
        Torrent $torrent,
    ): ?array {
        /** @var SonarrSeries $sonarrSeries */
        $sonarrSeries = $sonarrClient->findByTvdbId($torrent->series->thetvdb_id);
        $sonarEpisodes = $sonarrClient->getEpisodes($sonarrSeries->id);

        $commandFiles = [];

        /** @var SonarrEpisode $episode */
        foreach ($sonarEpisodes as $episode) {
            $file = collect($this->files)
                ->filter(
                    fn(File $file) => $torrent->season_number === $episode->seasonNumber &&
                        $file->episodeNumber === $episode->episodeNumber
                )
                ->first() ?? [];

            if (empty($file)) {
                continue;
            }

            $commandFiles[] = new importFile(
                $file->path,
                $sonarrSeries->id,
                $episode->seasonNumber,
                $episode->id,
            );
        }

        if (empty($commandFiles)) {
            app(AniarrLogger::class)->warning('[Sonarr] Нет файлов с эпизодами, fallback на scan');

            return null;
        }

        $command = $sonarrClient->sendManualImportCommand($commandFiles, 'move');

        app(AniarrLogger::class)->debug('[Sonarr] Ответ Sonarr ManualImport', [
            'command_id' => $command['id'] ?? null,
            'files_count' => count($commandFiles),
        ]);

        return $command;
    }

    /**
     * Ожидание завершения команды Sonarr до завершения или таймаута.
     * Возвращает статус команды: 'completed', 'failed' или 'timeout'.
     *
     * @param  SonarrClient  $sonarrClient  Экземпляр клиента Sonarr
     * @param  int  $commandId  ID команды Sonarr
     * @return string Статус команды: 'completed', 'failed' или 'timeout'
     */
    private function waitForSonarrCommand(SonarrClient $sonarrClient, int $commandId): string
    {
        $deadline = time() + self::SONARR_COMMAND_TIMEOUT;
        $pollCount = 0;
        while (time() < $deadline) {
            sleep(self::POLL_INTERVAL);
            $pollCount++;
            $cmd = $sonarrClient->getCommand($commandId);
            if ($cmd === null) {
                app(AniarrLogger::class)->debug('[Sonarr] Poll command: getCommand вернул null', [
                    'command_id' => $commandId,
                    'poll' => $pollCount,
                ]);

                continue;
            }
            $status = $cmd['status'] ?? '';
            if ($pollCount <= 2 || $status === 'completed' || $status === 'failed') {
                app(AniarrLogger::class)->info('[Sonarr] Статус команды Sonarr', [
                    'command_id' => $commandId,
                    'status' => $status,
                    'poll' => $pollCount,
                    'body' => $cmd['body'] ?? null,
                ]);
            }

            if ($status === 'completed' || $status === 'failed') {
                $method = $status === 'completed' ? 'info' : 'error';
                app(AniarrLogger::class)->{$method}('[Sonarr] Команда Sonarr завершена', [
                    'command_id' => $commandId,
                    'status' => $status,
                    'full_command_response' => $cmd,
                ]);

                return $status;
            }
        }
        app(AniarrLogger::class)->warning('[Sonarr] Таймаут ожидания команды Sonarr', [
            'command_id' => $commandId,
            'polls' => $pollCount,
        ]);

        return 'timeout';
    }

    /**
     * Извлекает номера эпизодов из путей файлов и добавляет их в метаданные файлов.
     */
    private function parseEpisodesFromFiles(): void
    {
        /** @var File $file */
        foreach ($this->files as &$file) {
            preg_match('/\[(\d{2})\]/', $file->path, $matches);
            if (! isset($matches[1])) {
                continue;
            }

            $episodeNumber = (int) $matches[1];
            if ($episodeNumber > 0) {
                $file->episodeNumber = $episodeNumber;
            }
        }
    }
}
