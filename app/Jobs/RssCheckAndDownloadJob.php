<?php

namespace App\Jobs;

use App\Enums\Status;
use App\Events\SeriesUpdated;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Integrations\QBittorrent\TorrentFileSelector;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\RssFeed;
use App\Models\Series;
use App\Models\Settings;
use App\Services\Logging\AniarrLogger;
use App\Services\Rss\BestTorrentPicker;
use App\Services\Rss\FeedChangesDetector;
use App\Services\Rss\RssParser;
use App\Services\SeriesStatsBroadcaster;
use Error;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * По расписанию: проверяет RSS у сериалов, уже синхронизированных с Sonarr,
 * при обновлениях добавляет торрент в qBittorrent (только новые серии) и запускает отслеживание.
 */
class RssCheckAndDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Проверяет RSS для сериалов, добавляет подходящие торренты в qBittorrent и запускает отслеживание загрузки.
     */
    public function handle(SonarrClient $sonarrClient, QBittorrentClient $qBittorrentClient, RssParser $rssParser): void
    {
        $logger = app(AniarrLogger::class);

        if (! $sonarrClient->testConnection()) {
            return;
        }

        if (! $qBittorrentClient->login()) {
            return;
        }

        $savePath = Settings::get('download_save_path', '');
        if ($savePath === '') {
            $folders = $sonarrClient->getRootFolders();
            $first = $folders[0]['path'] ?? null;
            $savePath = $first ? rtrim($first, '/') . '/Downloads' : '';
        }

        if ($savePath === '') {
            $logger->error('Ошибка: не настроен путь сохранения.');

            return;
        }

        $seriesList = Series::whereHas('rssFeeds')
            ->where('sonarr_connected', true)
            ->whereNull('active_torrent_hash')
            ->whereNotIn('status', [Status::DOWNLOADING_AVC, Status::DOWNLOADING_HEVC])
            ->with('rssFeeds')
            ->get();

        /** @var Series $series */
        foreach ($seriesList as $series) {
            $logger->setSeries($series->id);
            $feedProcessed = false;

            /** @var RssFeed $rssFeed */
            foreach ($series->rssFeeds as $rssFeed) {
                try {
                    $parseItems = $rssParser->parseFeed($rssFeed->rss_url);
                    if (empty($parseItems->items)) {
                        $logger->warning('Проверка пропущена: отсутствует данные в RSS-ленте', ['rss_url' => $rssFeed->rss_url]);

                        continue;
                    }

                    $detector = app(FeedChangesDetector::class);
                    if (! $detector->hasChanged($rssFeed, $parseItems)) {
                        $logger->info('RSS-лента не изменилась', ['rss_url' => $rssFeed->rss_url]);

                        continue;
                    }

                    $newItems = $detector->getNewItems($rssFeed, $parseItems);
                    $picked = app(BestTorrentPicker::class)->pick($newItems);
                    if ($picked === null) {
                        $logger->warning('Проверка пропущена: не найден подходящий torrent-файл', ['rss_url' => $rssFeed->rss_url]);

                        continue;
                    }

                    $isHevc = strtolower($picked->codec) == 'hevc';
                    $tag = $series->qbitTag();

                    $logger->info('Добавление торрента', ['url' => $picked->torrentUrl, 'tag' => $tag, 'rss_url' => $rssFeed->rss_url]);

                    $qBittorrentClient->addTorrentUrl($picked->torrentUrl, [
                        'stopped' => 'true', // Важное уточнение: в новой версии API параметр `paused` был заменён на `stopped`. https://github.com/qbittorrent/qBittorrent/issues/22766
                        'tags' => $tag,
                    ]);

                    sleep(2);

                    $torrents = $qBittorrentClient->getTorrentsByTag($tag);
                    $logger->info('Результат getTorrentsByTag', ['tag' => $tag, 'count' => count($torrents), 'hashes' => array_column($torrents, 'hash')]);
                    $firstTorrent = $torrents[0] ?? null;

                    if ($firstTorrent === null) {
                        $logger->error('Ошибка добавления торрента. Не найден торрент');

                        continue;
                    }

                    $hash = $firstTorrent['hash'] ?? '';

                    if (empty($hash)) {
                        $logger->error('Ошибка добавления торрента. Отсутствует хеш торрента');

                        continue;
                    }

                    $files = $this->waitForTorrentFiles($qBittorrentClient, $hash, 20);

                    if (empty($files)) {
                        $logger->error('Не удалось настроить приоритеты файлов в torrent. Загрузка отменена');
                        $qBittorrentClient->deleteTorrent($hash);

                        continue;
                    }

                    $indexesToDownload = app(TorrentFileSelector::class)->selectIndexes($series, $files);

                    $allIndexes = array_values(
                        array_unique(
                            array_map(
                                function (array $f) {
                                    return (int) ($f['index'] ?? $f['id'] ?? -1);
                                },
                                array_filter($files, fn(array $f) => isset($f['index']))
                            )
                        )
                    );

                    $allIndexes = array_values(
                        array_filter($allIndexes, fn(int $i) => $i >= 0)
                    );

                    if (empty($indexesToDownload) && ! empty($allIndexes)) {
                        $indexesToDownload = $allIndexes;
                    }
                    $indexesToSkip = array_values(array_diff($allIndexes, $indexesToDownload));

                    if (! empty($allIndexes) && ! empty($indexesToSkip)) {
                        $qBittorrentClient->setFilePriority($hash, implode('|', $indexesToSkip), 0);
                    } elseif (! empty($allIndexes) && ! empty($indexesToDownload)) {
                        $qBittorrentClient->setFilePriority($hash, implode('|', $indexesToDownload), 7);
                    }

                    $qBittorrentClient->startTorrent($hash);

                    $contentPath = $savePath;
                    if ($firstTorrent && isset($firstTorrent['content_path'])) {
                        $contentPath = $firstTorrent['content_path'];
                    } elseif ($firstTorrent && isset($firstTorrent['save_path'])) {
                        $contentPath = $firstTorrent['save_path'];
                    }

                    $status = $isHevc ? Status::DOWNLOADING_HEVC : Status::DOWNLOADING_AVC;
                    $series->update([
                        'has_avc' => ! $isHevc,
                        'has_hevc' => $isHevc,
                        'status' => $status,
                        'progress' => 0,
                        'active_torrent_hash' => $hash,
                        'active_download_path' => $contentPath,
                        'active_download_is_hevc' => $isHevc,
                        'error_message' => null,
                        'last_updated' => now(),
                    ]);

                    $rssFeed->update([
                        'last_rss_hash' => $parseItems[0]['guid'] ?? null,
                        'last_rss_check' => now(),
                    ]);

                    $codec = $picked->codec;
                    $logger->info('Торрент начал загрузку', ['codec' => $codec]);
                    broadcast(new SeriesUpdated($series->fresh()))->toOthers();
                    app(SeriesStatsBroadcaster::class)->broadcast();

                    WatchTorrentProgressJob::dispatch($series->id)->onQueue('downloads');

                    $feedProcessed = true;
                    break; // один сериал может иметь только один активный торрент за раз
                } catch (Throwable | Error $e) {
                    $logger->exception($e, $rssFeed->rss_url);
                }
            }

            if (! $feedProcessed) {
                $logger->info('Ни одна RSS-лента не подошла для сериала', ['series_id' => $series->id]);
            }
        }
    }

    /**
     * Ждём появления метаданных (списка файлов), иначе filePrio вернёт 409 или приоритеты не применятся.
     *
     * @return array<int, array>
     */
    private function waitForTorrentFiles(QBittorrentClient $qbit, string $hash, int $maxWaitSeconds): array
    {
        for ($i = 0; $i < $maxWaitSeconds; $i++) {
            sleep(1);
            $files = $qbit->getTorrentFiles($hash);

            $withIndex = array_filter($files, fn(array $f) => isset($f['index']));
            if (! empty($withIndex)) {
                return $files;
            }
        }

        return [];
    }
}
