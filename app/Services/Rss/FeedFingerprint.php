<?php

namespace App\Services\Rss;

use App\Services\Rss\Dto\FeedItem;
use App\Services\Rss\Dto\FeedItems;
use JsonException;

final class FeedFingerprint
{
    /**
     * @throws JsonException
     */
    public function make(FeedItems $items): string
    {
        $normalized = array_map(
            fn(FeedItem $item): array => [
                'guid' => $item->guid,
                'torrent_id' => $item->torrentId,
                'release_id' => $item->releaseId,
                'torrent_url' => $item->torrentUrl,
                'codec' => strtolower($item->codec),
                'quality' => $item->quality,
                'episodes' => array_values($item->episodes),
                'size' => $item->size,
                'published_at' => $item->pubDate,
            ],
            $items->items,
        );

        usort($normalized, function (array $left, array $right): int {
            return [$left['codec'], $left['guid']] <=> [$right['codec'], $right['guid']];
        });

        return hash(
            'sha256',
            json_encode($normalized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }
}
