<?php

namespace App\Services;

use App\Dto\FeedItemParsed;
use App\Dto\FeedParseResult;
use App\Dto\FeedTitleParsed;
use App\Dto\PendingAniarrLog;
use App\Enums\LogType;
use App\Models\Series;
use App\Models\Torrent;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RssParserService
{
    /**
     * Парсинг RSS-ленты и извлечение информации из торрента
     *
     * @param string $url URL RSS ленты
     * @return FeedParseResult Распарсенные данные
     */
    public function parseFeed(string $url): FeedParseResult
    {
        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                app(AniarrLogger::class)->error('Запрос RSS-ленты завершился ошибкой: ' . $response->status());
                return new FeedParseResult([]);
            }

            $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xml === false) {
                app(AniarrLogger::class)->error('Ошибка парсинга RSS-ленты: ' . $response->status());
                return new FeedParseResult([]);
            }

            $items = [];
            foreach ($xml->channel->item as $item) {
                $parsed = $this->parseItem($item);
                if ($parsed) {
                    $items[] = $parsed;
                }
            }

            return new FeedParseResult(items: $items);
        } catch (\Exception $e) {
            app(AniarrLogger::class)->exception($e);
            return new FeedParseResult([]);
        }
    }

    /**
     * Проверяет, изменилась ли лента новостей с момента последней проверки
     */
    public function hasFeedChanged(Series $series, array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $latestGuid = $items[0]['guid'] ?? null;

        return $series->last_rss_hash !== $latestGuid;
    }

    /**
     * Появились новые элементы с момента последней проверки
     */
    public function getNewItems(Series $series, array $items): array
    {
        if (empty($series->last_rss_hash)) {
            return $items; // First check, return all
        }

        $newItems = [];
        foreach ($items as $item) {
            if ($item['guid'] === $series->last_rss_hash) {
                break; // Reached last known item
            }
            $newItems[] = $item;
        }

        return $newItems;
    }

    /**
     * Сохраняет торрент в БД
     */
    public function saveTorrent(Series $series, array $item): ?Torrent
    {
        return Torrent::updateOrCreate(
            ['guid' => $item['guid']],
            [
                'series_id' => $series->id,
                'torrent_url' => $item['torrent_url'],
                'torrent_id' => $item['torrent_id'],
                'codec' => $item['codec'],
                'episodes' => $item['episodes'],
                'size' => $item['size'],
            ]
        );
    }

    /**
     * Парсинг отдельного RSS-элемента
     */
    protected function parseItem($item): ?FeedItemParsed
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

            if (!$metadata) {
                return null;
            }

            return new FeedItemParsed(
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
     * Parse title string to extract metadata
     *
     * Format: "Название | WEBRip 1080p | HEVC | 1-12"
     */
    protected function parseTitle(string $title): ?FeedTitleParsed
    {
        // Remove CDATA wrapper if present
        $title = trim($title);
        $data = explode(' | ', $title);

        if (count($data) != 4) {
            return null;
        }

        $codec = trim($data[2]);

        if (!$codec) {
            return null; // Not a valid anime release
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

        return new FeedTitleParsed($title, $codec, $episodes, $quality);
    }
}
