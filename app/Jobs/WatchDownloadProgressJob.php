<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Events\RealtimeChanged;
use App\Integrations\QBittorrent\Dto\Torrent as QBitTorrent;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Models\Download;
use App\Services\Logging\AniarrLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class WatchDownloadProgressJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10000;

    public $timeout = 0;

    public function __construct(public int $downloadId) {}

    public function handle(QBittorrentClient $qBittorrentClient): void
    {
        $download = Download::query()->with('season.series')->find($this->downloadId);
        if ($download === null || $download->status !== DownloadStatus::DOWNLOADING || ! $download->qbit_hash) {
            return;
        }

        $logger = app(AniarrLogger::class)->forDownload($download)->withSource('qbittorrent');

        try {
            if (! $qBittorrentClient->login()) {
                $this->release(5);

                return;
            }

            $current = $this->findCurrentTorrent($download, $qBittorrentClient);
            if ($current === null) {
                $fresh = Download::query()->find($download->id);
                if ($fresh?->status !== DownloadStatus::DOWNLOADING) {
                    return;
                }

                $logger->event('download.torrent_missing', '[QBittorrent] Download не найден по hash/tag', LogType::WARNING, [
                    'hash' => $download->qbit_hash,
                ]);
                $this->release(5);

                return;
            }

            $progress = (int) round(max(0, min(1, (float) $current->progress)) * 100);
            $eta = max(0, min(16_777_215, (int) $current->eta));

            $updated = Download::query()
                ->whereKey($download->id)
                ->where('status', DownloadStatus::DOWNLOADING->value)
                ->update(['progress' => $progress, 'eta_seconds' => $eta]);

            if ($updated === 0) {
                return;
            }

            $this->broadcastChanged($download);

            $isDone = $progress >= 100 || in_array(
                $current->state,
                ['completed', 'uploading', 'stalledUP', 'stoppedUP', 'stoppedUp'],
                true,
            );

            if (! $isDone) {
                $fresh = Download::query()->find($download->id);
                if ($fresh?->status !== DownloadStatus::DOWNLOADING) {
                    return;
                }

                $delay = (int) max(1, min(15, ceil(max(1, $eta) / 7200)));
                $this->release($delay);

                return;
            }

            $importing = Download::query()
                ->whereKey($download->id)
                ->where('status', DownloadStatus::DOWNLOADING->value)
                ->update([
                    'status' => DownloadStatus::IMPORTING,
                    'progress' => 100,
                    'eta_seconds' => 0,
                ]);

            if ($importing === 0) {
                return;
            }

            $this->broadcastChanged($download);

            $logger->event('download.downloaded', '[QBittorrent] Загрузка завершена', LogType::INFO);

            ImportDownloadToSonarrJob::dispatch($download->id)->onQueue('downloads');
        } catch (Throwable $e) {
            $fresh = Download::query()->find($this->downloadId);
            if ($fresh?->status === DownloadStatus::CANCELLED) {
                return;
            }

            $logger->exception($e, event: 'download.watch_failed');
            throw $e;
        }
    }

    private function broadcastChanged(Download $download): void
    {
        event(new RealtimeChanged(
            resource: 'download',
            action: 'updated',
            id: $download->id,
            seriesId: $download->season?->series_id,
            seasonId: $download->season_id,
            downloadId: $download->id,
        ));
    }

    private function findCurrentTorrent(Download $download, QBittorrentClient $qBittorrentClient): ?QBitTorrent
    {
        $torrents = $qBittorrentClient->getTorrentsByTag($download->qbit_tag ?? '');
        foreach ($torrents as $torrent) {
            if ($torrent->hash === $download->qbit_hash) {
                return $torrent;
            }
        }

        return null;
    }

    public function uniqueId(): string
    {
        return 'watch-download:'.$this->downloadId;
    }

    public function uniqueFor(): int
    {
        return 60;
    }
}
