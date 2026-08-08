<?php

namespace App\Jobs;

use App\Events\SeriesUpdated;
use App\Integrations\QBittorrent\Dto\File;
use App\Integrations\QBittorrent\Dto\Torrent;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Integrations\QBittorrent\TorrentFileSelector;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Episode;
use App\Models\RssFeed;
use App\Models\Series;
use App\Models\Settings;
use App\Models\Torrent as ModelsTorrent;
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
use Illuminate\Support\Arr;
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
            $savePath = Arr::has($folders, '0.path') ? rtrim(Arr::get($folders, '0.path'), '/') . '/Downloads' : '';
        }

        if ($savePath === '') {
            $logger->error('[Sonarr] Ошибка: не настроен путь сохранения.');

            return;
        }

        $seriesList = Series::query()
            ->whereHas('rssFeeds')
            ->whereHas('episodes', fn($query) => $query->whereNull('downloaded_at'))
            ->doesntHave('torrents', callback: fn($query) => $query->where('downloaded', false))
            ->whereNotNull('sonarr_id')
            ->with([
                'rssFeeds',
                'episodes' => fn($query) => $query->whereNull('downloaded_at')
            ])
            ->get();

        /** @var Series $series */
        foreach ($seriesList as $series) {
            $logger->setSeries($series->id);
            $feedProcessed = false;

            /** @var RssFeed $rssFeed */
            foreach ($series->rssFeeds as $rssFeed) {
                $hasEpisodes = $series->episodes->filter(fn(Episode $episode) => $episode->season_number == $rssFeed->season_number)->count();
                if (!$hasEpisodes) {
                    $logger->info('Проверка пропущена: отсутствует RSS-лента', ['rss_url' => $rssFeed->rss_url]);

                    continue;
                }

                try {
                    $parseItems = $rssParser->parseFeed($rssFeed->rss_url);
                    if (empty($parseItems->items)) {
                        $logger->warning('[RSS] Проверка пропущена: отсутствует данные в RSS-ленте', ['rss_url' => $rssFeed->rss_url]);

                        continue;
                    }

                    $detector = app(FeedChangesDetector::class);
                    if (! $detector->hasChanged($rssFeed, $parseItems)) {
                        $logger->info('[RSS] RSS-лента не изменилась', ['rss_url' => $rssFeed->rss_url]);

                        continue;
                    }

                    $newItems = $detector->getNewItems($rssFeed, $parseItems);
                    $picked = app(BestTorrentPicker::class)->pick($newItems);
                    if ($picked === null) {
                        $logger->warning('[Torrent] Проверка пропущена: не найден подходящий torrent-файл', ['rss_url' => $rssFeed->rss_url]);

                        continue;
                    }

                    $tag = $this->getTag($series, $rssFeed);

                    $logger->info('[Torrent] Добавление торрента', ['url' => $picked->torrentUrl, 'tag' => $tag, 'rss_url' => $rssFeed->rss_url]);

                    $qBittorrentClient->addTorrentUrl($picked->torrentUrl, [
                        'stopped' => 'true', // Важное уточнение: в новой версии API параметр `paused` был заменён на `stopped`. https://github.com/qbittorrent/qBittorrent/issues/22766
                        'tags' => $tag,
                    ]);

                    sleep(2);

                    $torrents = $qBittorrentClient->getTorrentsByTag($tag);
                    $logger->debug('[Torrent] Результат getTorrentsByTag', ['tag' => $tag, 'count' => count($torrents), 'hashes' => array_column($torrents, 'hash')]);
                    /** @var Torrent $firstTorrent */
                    $firstTorrent = $torrents[0] ?? null;

                    if ($firstTorrent === null) {
                        $logger->error('[Torrent] Ошибка добавления торрента. Не найден торрент');

                        continue;
                    }

                    $hash = $firstTorrent->hash ?? '';

                    if (empty($hash)) {
                        $logger->error('[Torrent] Ошибка добавления торрента. Отсутствует хеш торрента');

                        continue;
                    }

                    $files = $this->waitForTorrentFiles($qBittorrentClient, $hash);

                    if (empty($files)) {
                        $logger->error('[Torrent] Не удалось настроить приоритеты файлов в torrent. Загрузка отменена');
                        $qBittorrentClient->deleteTorrent($hash);

                        continue;
                    }

                    $episodes = $series->episodes()->where('season_number', $rssFeed->season_number)->whereNull('downloaded_at')->get();
                    $indexesToDownload = app(TorrentFileSelector::class)->selectIndexes($episodes, $files);

                    $fileToDownload = array_filter($files, fn(File $file) => in_array($file->index, $indexesToDownload));
                    $fileToSkip = array_filter($files, fn(File $file) => !in_array($file->index, $indexesToDownload));

                    $qBittorrentClient->setFilePriority($hash, implode('|', array_map(fn(File $file) => $file->index, $fileToDownload)), 7);
                    $qBittorrentClient->setFilePriority($hash, implode('|', array_map(fn(File $file) => $file->index, $fileToSkip)), 0);

                    /** @var ModelsTorrent */
                    $torrent = $series->torrents()->firstOrCreate([
                        'season_number' => $rssFeed->season_number,
                        'torrent_url' => $picked->torrentUrl,
                    ], [
                        'codec' => strtolower($picked->codec),
                        'progress' => 0,
                        'downloaded' => false,
                        'active_torrent_hash' => $hash,
                    ]);

                    /** @var Episode $episode */
                    foreach ($episodes as $episode) {
                        $episode->torrent()->associate($torrent)->save();
                    }

                    $qBittorrentClient->startTorrent($hash);

                    $logger->info('[Torrent] Торрент начал загрузку', ['codec' => $picked->codec]);
                    broadcast(new SeriesUpdated($series->fresh()))->toOthers();
                    app(SeriesStatsBroadcaster::class)->broadcast();

                    WatchTorrentProgressJob::dispatch($torrent->id)->onQueue('downloads');

                    $feedProcessed = true;
                } catch (Throwable | Error $e) {
                    $logger->exception($e, $rssFeed->rss_url);
                }
            }

            if (! $feedProcessed) {
                $logger->warning('[RSS] Ни одна RSS-лента не подошла для сериала', ['series_id' => $series->id]);
            }
        }
    }

    /**
     * Ждём появления метаданных (списка файлов), иначе filePrio вернёт 409 или приоритеты не применятся.
     *
     * @return array<int, array>
     */
    private function waitForTorrentFiles(QBittorrentClient $qBittorrentClient, string $hash, int $maxWaitSeconds = 20): array
    {
        for ($i = 0; $i < $maxWaitSeconds; $i++) {
            sleep(1);
            return $qBittorrentClient->getTorrentFiles($hash);
        }

        return [];
    }

    private function getTag(Series $series, RssFeed $rssFeed): string
    {
        return "aniarr-{$series->id}-{$rssFeed->season_number}";
    }
}
