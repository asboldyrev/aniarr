<?php

namespace App\Services\Rss;

use App\Services\Rss\Dto\FeedItem;
use App\Services\Rss\Dto\FeedItems;
use App\Services\Rss\Dto\FeedTitle;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

/**
 * Парсит RSS-ленту и преобразует её элементы в DTO без побочных эффектов.
 */
final class RssParser
{
    public function parseFeed(string $url): FeedItems
    {
        $response = Http::timeout(30)->get($url);

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new RuntimeException(
                "RSS request failed with status {$response->status()}",
                previous: $e,
            );
        }

        $xml = simplexml_load_string($response->body(), SimpleXMLElement::class, LIBXML_NOCDATA);

        if ($xml === false || ! isset($xml->channel)) {
            throw new RuntimeException('Unable to parse RSS feed XML.');
        }

        $items = [];

        foreach ($xml->channel->item as $item) {
            $parsed = $this->parseItem($item);

            if ($parsed !== null) {
                $items[] = $parsed;
            }
        }

        return new FeedItems($items);
    }

    private function parseItem(SimpleXMLElement $item): ?FeedItem
    {
        $title = trim((string) $item->title);
        $guid = trim((string) $item->guid);
        $pubDate = trim((string) $item->pubDate);
        $torrentId = trim((string) $item->torrentId);
        $releaseId = trim((string) $item->releaseId);

        $enclosure = $item->enclosure;
        $torrentUrl = trim((string) ($enclosure['url'] ?? ''));
        $size = (int) ($enclosure['length'] ?? 0);

        if ($guid === '' || $torrentUrl === '') {
            return null;
        }

        $metadata = $this->parseTitle($title);
        if ($metadata === null) {
            return null;
        }

        return new FeedItem(
            title: $metadata->title,
            guid: $guid,
            torrentUrl: $torrentUrl,
            torrentId: $torrentId !== '' ? (int) $torrentId : null,
            releaseId: $releaseId !== '' ? (int) $releaseId : null,
            pubDate: $pubDate,
            size: $size,
            codec: strtolower($metadata->codec),
            episodes: $metadata->episodes,
            quality: $metadata->quality,
        );
    }

    private function parseTitle(string $title): ?FeedTitle
    {
        $parts = explode(' | ', trim($title));
        if (count($parts) !== 4) {
            return null;
        }

        $codec = strtoupper(trim($parts[2]));
        if (! in_array($codec, ['AVC', 'HEVC'], true)) {
            return null;
        }

        $range = trim($parts[3]);
        if (preg_match('/^(\d+)(?:-(\d+))?$/', $range, $matches) !== 1) {
            return null;
        }

        $firstEpisode = (int) $matches[1];
        $lastEpisode = isset($matches[2]) ? (int) $matches[2] : $firstEpisode;

        if ($firstEpisode <= 0 || $lastEpisode < $firstEpisode) {
            return null;
        }

        $quality = match (true) {
            stripos($parts[1], '2160p') !== false, stripos($parts[1], '4K') !== false => '2160p',
            stripos($parts[1], '720p') !== false => '720p',
            default => '1080p',
        };

        return new FeedTitle(
            trim($parts[0]),
            $codec,
            range($firstEpisode, $lastEpisode),
            $quality,
        );
    }
}
