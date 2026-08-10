<?php

namespace App\Jobs;

use App\Enums\LogType;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Models\Download;
use App\Services\Logging\AniarrLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

final class CleanupQBitTorrentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $downloadId) {}

    public function handle(QBittorrentClient $qBittorrentClient): void
    {
        $download = Download::query()->with('season.series')->find($this->downloadId);
        if ($download === null || ! $download->qbit_hash) {
            return;
        }

        $logger = app(AniarrLogger::class)->forDownload($download)->withSource('qbittorrent');

        if (! $qBittorrentClient->login()) {
            throw new RuntimeException('Не удалось подключиться к qBittorrent для очистки.');
        }

        $hash = $download->qbit_hash;
        $exists = false;

        foreach ($qBittorrentClient->getTorrentsByTag($download->qbit_tag ?? '') as $torrent) {
            if ($torrent->hash === $hash) {
                $exists = true;
                break;
            }
        }

        if ($exists && ! $qBittorrentClient->deleteTorrent($hash)) {
            throw new RuntimeException('Не удалось удалить torrent из qBittorrent.');
        }

        if ($download->qbit_tag) {
            $qBittorrentClient->deleteTags($download->qbit_tag);
        }

        $download->update(['qbit_hash' => null, 'eta_seconds' => 0]);

        $logger->event('download.cleaned_up', '[QBittorrent] Очистка Download завершена', LogType::INFO, [
            'hash' => $hash,
            'torrent_existed' => $exists,
        ]);
    }
}
