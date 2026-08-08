<?php

namespace App\Services\Rss;

use App\Actions\Torrents\SaveTorrent;
use App\Models\Series;
use App\Models\Torrent;
use App\Services\Logging\AniarrLogger;
use App\Services\Rss\Dto\FeedItem;
use App\Services\Rss\Dto\FeedItems;
use App\Services\Rss\Dto\FeedTitle;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * Сервис парсинга RSS-лент торрент-трекеров, извлечения метаданных и сохранения торрентов.
 */
class RssParser
{
    /**
     * Парсинг RSS-ленты и извлечение информации из торрента
     *
     * @param  string  $url  URL RSS ленты
     * @return FeedItems Распарсенные данные
     */
    public function parseFeed(string $url): FeedItems
    {
        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                app(AniarrLogger::class)->error('[RSS] Запрос завершился ошибкой: ' . $response->status());

                return new FeedItems([]);
            }

            $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xml === false) {
                app(AniarrLogger::class)->error('[RSS] Ошибка парсинга ленты: ' . $response->status());

                return new FeedItems([]);
            }

            $items = [];
            foreach ($xml->channel->item as $item) {
                $parsed = $this->parseItem($item);
                if ($parsed) {
                    $items[] = $parsed;
                }
            }

            return new FeedItems(items: $items);
        } catch (Exception $e) {
            app(AniarrLogger::class)->exception($e);

            return new FeedItems([]);
        }
    }

    /**
     * Сохраняет торрент в БД
     *
     * @deprecated
     */
    public function saveTorrent(Series $series, FeedItem $item): ?Torrent
    {
        return app(SaveTorrent::class)->execute($series, $item);
    }

    /**
     * Парсинг отдельного RSS-элемента
     */
    protected function parseItem($item): ?FeedItem
    {
        try {
            $title = (string) $item->title;
            $guid = (string) $item->guid;
            $pubDate = (string) $item->pubDate;
            $torrentId = (int) $item->torrentId ?? null;
            $releaseId = (int) $item->releaseId ?? null;

            // Parse enclosure (torrent file)
            $enclosure = $item->enclosure;
            $torrentUrl = (string) $enclosure['url'];
            $size = (int) ($enclosure['length'] ?? 0);

            // Parse title for metadata
            // Format: "Название | WEBRip 1080p | HEVC | 1-12"
            $metadata = $this->parseTitle($title);

            if (! $metadata) {
                return null;
            }

            return new FeedItem(
                $metadata->title,
                $guid,
                $torrentUrl,
                $torrentId,
                $releaseId,
                $pubDate,
                $size,
                $metadata->codec,
                $metadata->episodes,
                $metadata->quality,
            );
        } catch (Exception $e) {
            app(AniarrLogger::class)->exception($e);

            return null;
        }
    }

    /**
     * Парсит заголовок и разбивает достаёт метаданные
     *
     * Формат: "Название | WEBRip 1080p | HEVC | 1-12"
     */
    protected function parseTitle(string $title): ?FeedTitle
    {
        // Remove CDATA wrapper if present
        $title = trim($title);
        $data = explode(' | ', $title);

        if (count($data) != 4) {
            return null;
        }

        $codec = trim($data[2]);

        if (! $codec) {
            return null;
        }

        $range = explode('-', $data[3]);
        $episodes = range($range[0], $range[1]);

        if (empty($episodes)) {
            return null;
        }

        // Extract quality
        $quality = '1080p';
        if (stripos($data[1], '720p') !== false) {
            $quality = '720p';
        } elseif (stripos($data[1], '1080p') !== false) {
            $quality = '1080p';
        } elseif (stripos($data[1], '2160p') !== false || stripos($data[1], '4K') !== false) {
            $quality = '2160p';
        }

        $title = $data[0];

        return new FeedTitle($title, $codec, $episodes, $quality);
    }
}
