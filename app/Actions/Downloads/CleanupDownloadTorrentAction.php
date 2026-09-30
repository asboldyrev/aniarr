<?php

namespace App\Actions\Downloads;

use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Models\Download;
use App\Services\Logging\AniarrLogger;
use RuntimeException;

final class CleanupDownloadTorrentAction
{
    public function __construct(
        private readonly QBittorrentClient $qBittorrentClient,
    ) {}

    public function execute(Download $download): void
    {
        if (! $download->qbit_hash && ! $download->qbit_tag) {
            return;
        }

        if (! $this->qBittorrentClient->login()) {
            throw new RuntimeException('Не удалось подключиться к qBittorrent для очистки.');
        }

        $hashes = [];

        if ($download->qbit_hash) {
            $hashes[] = $download->qbit_hash;
        }

        if ($download->qbit_tag) {
            foreach ($this->qBittorrentClient->getTorrentsByTag($download->qbit_tag) as $torrent) {
                $hashes[] = $torrent->hash;
            }
        }

        $hashes = array_values(array_unique(array_filter($hashes)));
        $deletedHashes = [];
        $protectedHashes = [];

        foreach ($hashes as $hash) {
            $usedByActiveDownload = Download::query()
                ->whereKeyNot($download->id)
                ->where('qbit_hash', $hash)
                ->whereIn('status', DownloadStatus::activeValues())
                ->exists();

            if ($usedByActiveDownload) {
                $protectedHashes[] = $hash;
                continue;
            }

            if (! $this->qBittorrentClient->deleteTorrent($hash)) {
                throw new RuntimeException("Не удалось удалить torrent {$hash} из qBittorrent.");
            }

            $deletedHashes[] = $hash;
        }

        if ($download->qbit_tag) {
            $this->qBittorrentClient->deleteTags($download->qbit_tag);
        }

        $download->update([
            'qbit_hash' => null,
            'eta_seconds' => 0,
        ]);

        app(AniarrLogger::class)
            ->forDownload($download)
            ->withSource('qbittorrent')
            ->event('download.cleaned_up', '[QBittorrent] Очистка Download завершена', LogType::INFO, [
                'deleted_hashes' => $deletedHashes,
                'protected_hashes' => $protectedHashes,
            ]);
    }
}
