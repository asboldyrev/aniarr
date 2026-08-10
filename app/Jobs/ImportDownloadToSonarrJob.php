<?php

namespace App\Jobs;

use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Enums\DownloadStatus;
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

    public function __construct(
        public int $downloadId,
    ) {}

    public function handle(
        SonarrClient $sonarrClient,
        QBittorrentClient $qBittorrentClient,
        SyncSeriesStateFromSonarrAction $syncAction,
    ): void {
        /** @var Download|null $download */
        $download = Download::query()
            ->with(['season.series', 'release', 'items.episode'])
            ->find($this->downloadId);

        if ($download === null || $download->status !== DownloadStatus::IMPORTING) {
            return;
        }

        $logger = app(AniarrLogger::class);
        $logger->setSeries($download->season->series_id);

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

            $download->load(['release', 'items.episode']);
            foreach ($download->items as $item) {
                if (! $item->episode->has_file) {
                    throw new RuntimeException(
                        'Sonarr не подтвердил файл эпизода '.$item->episode->episode_number.'.',
                    );
                }

                if ($item->episode->file_codec !== $download->release->codec) {
                    throw new RuntimeException(
                        'Codec эпизода '.$item->episode->episode_number.' не соответствует импортированному Release.',
                    );
                }
            }

            $download->update([
                'status' => DownloadStatus::COMPLETED,
                'completed_at' => now(),
                'error_message' => null,
            ]);

            $logger->info('[Sonarr] Download успешно импортирован', [
                'download_id' => $download->id,
                'release_id' => $download->release_id,
            ]);

            CleanupQBitTorrentJob::dispatch($download->id)->onQueue('downloads');
            RefreshJellyfinLibraryJob::dispatch($download->id)->onQueue('downloads');
            PlanSeasonDownloadsJob::dispatch($download->season_id)->onQueue('downloads');
        } catch (Throwable $e) {
            $logger->exception($e);
            throw $e;
        } finally {
            $logger->resetSeries();
        }
    }

    private function findCurrentTorrent(
        Download $download,
        QBittorrentClient $qBittorrentClient,
    ): ?QBitTorrent {
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
        Download::query()->whereKey($this->downloadId)->update([
            'status' => DownloadStatus::FAILED,
            'failed_at' => now(),
            'error_message' => $e?->getMessage(),
        ]);
    }
}
