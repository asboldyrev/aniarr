<?php

namespace App\Jobs;

use App\Actions\Downloads\CompleteImportedDownloadAction;
use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Integrations\QBittorrent\Dto\Torrent as QBitTorrent;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Integrations\Sonarr\Dto\importFile;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Download;
use App\Services\Logging\AniarrLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

final class ImportDownloadToSonarrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SONARR_COMMAND_TIMEOUT = 300;

    private const POLL_INTERVAL = 3;

    public int $tries = 5;

    public $timeout = 360;

    public function __construct(public int $downloadId) {}

    public function handle(
        SonarrClient $sonarrClient,
        QBittorrentClient $qBittorrentClient,
        SyncSeriesStateFromSonarrAction $syncAction,
        CompleteImportedDownloadAction $completeImportedDownload,
    ): void {
        $download = Download::query()->with(['season.series', 'release', 'items.episode'])->find($this->downloadId);
        if ($download === null || $download->status !== DownloadStatus::IMPORTING) {
            return;
        }

        $logger = app(AniarrLogger::class)->forDownload($download)->withSource('sonarr');

        try {
            if (! $sonarrClient->testConnection()) {
                throw new RuntimeException('Sonarr недоступен.');
            }

            if ($download->imported_at === null) {
                $current = $this->findCurrentTorrent($download, $qBittorrentClient);
                if ($current === null) {
                    throw new RuntimeException('Torrent для импорта не найден в qBittorrent.');
                }

                $commandFiles = $this->buildImportFiles($download, $current);
                if ($commandFiles === []) {
                    throw new RuntimeException('Нет файлов для ManualImport в Sonarr.');
                }

                $logger->event('download.importing', '[Sonarr] Импорт Download запущен', LogType::INFO, [
                    'files_count' => count($commandFiles),
                ]);

                $command = $sonarrClient->sendManualImportCommand($commandFiles, 'move');
                $commandId = (int) ($command['id'] ?? 0);
                if ($commandId <= 0) {
                    throw new RuntimeException('Sonarr не вернул command id для ManualImport.');
                }

                $status = $this->waitForSonarrCommand($sonarrClient, $commandId);
                if ($status !== 'completed') {
                    throw new RuntimeException(
                        $status === 'failed'
                            ? 'ManualImport Sonarr завершился с ошибкой.'
                            : 'Таймаут ожидания ManualImport Sonarr.',
                    );
                }

                $download->update(['imported_at' => now()]);
            }

            $series = $download->season->series;
            $sonarrSeries = $sonarrClient->getSeriesByTvdbId($series->thetvdb_id);
            if ($sonarrSeries === null) {
                throw new RuntimeException('Сериал не найден среди добавленных сериалов Sonarr после импорта.');
            }

            $syncAction->execute($series, $sonarrSeries, $sonarrClient);

            if (! $completeImportedDownload->execute($download)) {
                throw new RuntimeException('Sonarr ещё не подтвердил импортированные файлы Download.');
            }
        } catch (Throwable $e) {
            $logger->exception($e, event: 'download.import_failed');
            throw $e;
        }
    }

    private function findCurrentTorrent(Download $download, QBittorrentClient $qBittorrentClient): ?QBitTorrent
    {
        if (! $qBittorrentClient->login()) {
            return null;
        }

        foreach ($qBittorrentClient->getTorrentsByTag($download->qbit_tag ?? '') as $torrent) {
            if ($torrent->hash === $download->qbit_hash) {
                return $torrent;
            }
        }

        return null;
    }

    /** @return array<importFile> */
    private function buildImportFiles(Download $download, QBitTorrent $torrent): array
    {
        $files = [];
        $savePath = rtrim($torrent->save_path, '/');
        $seriesId = $download->season->series->sonarr_id;

        if (! $seriesId) {
            throw new RuntimeException('У Series отсутствует sonarr_id.');
        }

        foreach ($download->items as $item) {
            $episode = $item->episode;
            if (! $episode->sonarr_id || ! $item->torrent_file_name) {
                continue;
            }

            $files[] = new importFile(
                path: $savePath.'/'.ltrim($item->torrent_file_name, '/'),
                seriesId: $seriesId,
                seasonNumber: $download->season->number,
                episodeId: $episode->sonarr_id,
            );
        }

        return $files;
    }

    private function waitForSonarrCommand(SonarrClient $sonarrClient, int $commandId): string
    {
        $deadline = time() + self::SONARR_COMMAND_TIMEOUT;

        while (time() < $deadline) {
            sleep(self::POLL_INTERVAL);
            $command = $sonarrClient->getCommand($commandId);
            if ($command === null) {
                continue;
            }

            $status = (string) ($command['status'] ?? '');
            if (in_array($status, ['completed', 'failed'], true)) {
                return $status;
            }
        }

        return 'timeout';
    }

    public function failed(?Throwable $e): void
    {
        $download = Download::query()->with('season')->find($this->downloadId);
        if ($download === null) {
            return;
        }

        if ($download->imported_at !== null) {
            $download->update([
                'error_message' => $e?->getMessage(),
            ]);

            app(AniarrLogger::class)
                ->forDownload($download)
                ->withSource('sonarr')
                ->event(
                    'download.verification_pending',
                    '[Sonarr] Импорт выполнен, ожидается подтверждение состояния',
                    LogType::WARNING,
                    ['error' => $e?->getMessage()],
                );

            SyncSeriesWithSonarrJob::dispatch($download->season->series_id)
                ->onQueue('downloads');

            return;
        }

        $download->update([
            'status' => DownloadStatus::FAILED,
            'failed_at' => now(),
            'error_message' => $e?->getMessage(),
        ]);

        app(AniarrLogger::class)
            ->forDownload($download)
            ->withSource('sonarr')
            ->event('download.failed', '[Sonarr] Download завершился ошибкой', LogType::ERROR, [
                'error' => $e?->getMessage(),
            ]);
    }
}
