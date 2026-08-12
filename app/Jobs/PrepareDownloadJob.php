<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Integrations\QBittorrent\Dto\File;
use App\Integrations\QBittorrent\Dto\Torrent as QBitTorrent;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Integrations\QBittorrent\TorrentFileSelector;
use App\Models\Download;
use App\Models\Settings;
use App\Services\Logging\AniarrLogger;
use App\Support\EpisodeNumberParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

final class PrepareDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $downloadId) {}

    public function handle(QBittorrentClient $qBittorrentClient, TorrentFileSelector $fileSelector): void
    {
        $download = Download::query()->with(['season.series', 'release', 'items.episode'])->find($this->downloadId);
        if ($download === null || ! in_array($download->status, [DownloadStatus::PENDING, DownloadStatus::PREPARING], true)) {
            return;
        }

        $logger = app(AniarrLogger::class)->forDownload($download)->withSource('qbittorrent');

        try {
            if (! $qBittorrentClient->login()) {
                throw new RuntimeException('Не удалось подключиться к qBittorrent.');
            }

            $claimed = Download::query()
                ->whereKey($download->id)
                ->whereIn('status', [DownloadStatus::PENDING->value, DownloadStatus::PREPARING->value])
                ->update([
                    'status' => DownloadStatus::PREPARING,
                    'error_message' => null,
                ]);

            if ($claimed === 0) {
                return;
            }

            $download->refresh();
            $logger->event('download.preparing', '[QBittorrent] Подготовка загрузки', LogType::INFO);

            $tag = $download->qbit_tag ?: 'aniarr-download-'.$download->id;
            $download->update(['qbit_tag' => $tag]);

            $torrent = $this->resolveTorrent($download, $qBittorrentClient, $tag);
            $hash = $torrent->hash ?? '';
            if ($hash === '') {
                throw new RuntimeException('qBittorrent не вернул hash добавленного торрента.');
            }

            if ($this->cancelledWhilePreparing($qBittorrentClient, $hash)) {
                return;
            }

            $download->update(['qbit_hash' => $hash]);

            $files = $this->waitForTorrentFiles($qBittorrentClient, $hash);
            if ($files === []) {
                throw new RuntimeException('qBittorrent не вернул список файлов торрента.');
            }

            $episodes = $download->items->pluck('episode');
            $indexesToDownload = $fileSelector->selectIndexes($episodes, $files);
            if ($indexesToDownload === []) {
                throw new RuntimeException('Не удалось сопоставить файлы торрента с эпизодами Download.');
            }

            $selectedFiles = array_values(array_filter($files, fn (File $file): bool => in_array($file->index, $indexesToDownload, true)));
            $skippedFiles = array_values(array_filter($files, fn (File $file): bool => ! in_array($file->index, $indexesToDownload, true)));

            $this->persistSelectedFiles($download, $selectedFiles);

            if ($selectedFiles !== [] && ! $qBittorrentClient->setFilePriority($hash, implode('|', array_map(fn (File $file): int => $file->index, $selectedFiles)), 7)) {
                throw new RuntimeException('Не удалось установить приоритет выбранных файлов qBittorrent.');
            }

            if ($skippedFiles !== [] && ! $qBittorrentClient->setFilePriority($hash, implode('|', array_map(fn (File $file): int => $file->index, $skippedFiles)), 0)) {
                throw new RuntimeException('Не удалось отключить лишние файлы qBittorrent.');
            }

            if ($this->cancelledWhilePreparing($qBittorrentClient, $hash)) {
                return;
            }

            if (! $qBittorrentClient->startTorrent($hash)) {
                throw new RuntimeException('Не удалось запустить torrent в qBittorrent.');
            }

            $started = Download::query()
                ->whereKey($download->id)
                ->where('status', DownloadStatus::PREPARING->value)
                ->update([
                    'status' => DownloadStatus::DOWNLOADING,
                    'started_at' => $download->started_at ?? now(),
                ]);

            if ($started === 0) {
                $this->cleanupCancelledTorrent($qBittorrentClient, $hash);

                return;
            }

            $download->refresh();

            $logger->event('download.started', '[QBittorrent] Загрузка запущена', LogType::INFO, [
                'hash' => $hash,
                'files_count' => count($selectedFiles),
            ]);

            WatchDownloadProgressJob::dispatch($download->id)->onQueue('downloads');
        } catch (Throwable $e) {
            $fresh = Download::query()->find($this->downloadId);
            if ($fresh?->status === DownloadStatus::CANCELLED) {
                return;
            }

            $logger->exception($e, event: 'download.prepare_failed');
            throw $e;
        }
    }

    private function cancelledWhilePreparing(QBittorrentClient $qBittorrentClient, string $hash): bool
    {
        $download = Download::query()->find($this->downloadId);
        if ($download?->status !== DownloadStatus::CANCELLED) {
            return false;
        }

        $this->cleanupCancelledTorrent($qBittorrentClient, $hash, $download->qbit_tag);

        return true;
    }

    private function cleanupCancelledTorrent(
        QBittorrentClient $qBittorrentClient,
        string $hash,
        ?string $tag = null,
    ): void {
        $qBittorrentClient->deleteTorrent($hash);

        $tag ??= Download::query()->whereKey($this->downloadId)->value('qbit_tag');
        if ($tag) {
            $qBittorrentClient->deleteTags($tag);
        }
    }

    private function resolveTorrent(Download $download, QBittorrentClient $qBittorrentClient, string $tag): QBitTorrent
    {
        $existing = $qBittorrentClient->getTorrentsByTag($tag);

        if ($download->qbit_hash) {
            foreach ($existing as $torrent) {
                if ($torrent->hash === $download->qbit_hash) {
                    return $torrent;
                }
            }
        }

        if ($existing !== []) {
            return $existing[0];
        }

        $options = ['stopped' => 'true', 'tags' => $tag];
        $savePath = Settings::get('download_save_path', '');
        if ($savePath !== '') {
            $options['savepath'] = $savePath;
        }

        if (! $qBittorrentClient->addTorrentUrl($download->release->torrent_url, $options)) {
            throw new RuntimeException('Не удалось добавить torrent в qBittorrent.');
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            sleep(1);
            $torrents = $qBittorrentClient->getTorrentsByTag($tag);
            if ($torrents !== []) {
                return $torrents[0];
            }
        }

        throw new RuntimeException('Добавленный torrent не найден по тегу qBittorrent.');
    }

    /** @return array<File> */
    private function waitForTorrentFiles(QBittorrentClient $qBittorrentClient, string $hash): array
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $files = $qBittorrentClient->getTorrentFiles($hash);
            if ($files !== []) {
                return $files;
            }
            sleep(1);
        }

        return [];
    }

    /** @param array<File> $selectedFiles */
    private function persistSelectedFiles(Download $download, array $selectedFiles): void
    {
        $byEpisode = [];
        foreach ($selectedFiles as $file) {
            $episodeNumber = EpisodeNumberParser::fromFileName($file->name);
            if ($episodeNumber !== null) {
                $byEpisode[$episodeNumber] = $file;
            }
        }

        foreach ($download->items as $item) {
            $file = $byEpisode[$item->episode->episode_number] ?? null;
            if ($file === null) {
                throw new RuntimeException('Не найден файл для эпизода '.$item->episode->episode_number.'.');
            }
            $item->update(['torrent_file_index' => $file->index, 'torrent_file_name' => $file->name]);
        }
    }

    public function failed(?Throwable $e): void
    {
        $download = Download::query()->with('season')->find($this->downloadId);
        if ($download === null || $download->status === DownloadStatus::CANCELLED) {
            return;
        }

        $download->update([
            'status' => DownloadStatus::FAILED,
            'failed_at' => now(),
            'error_message' => $e?->getMessage(),
        ]);

        app(AniarrLogger::class)
            ->forDownload($download)
            ->withSource('qbittorrent')
            ->event('download.failed', '[QBittorrent] Подготовка загрузки завершилась ошибкой', LogType::ERROR, [
                'error' => $e?->getMessage(),
            ]);
    }
}
