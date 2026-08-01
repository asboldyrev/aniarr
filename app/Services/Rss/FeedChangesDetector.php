<?php

namespace App\Services\Rss;

use App\Models\RssFeed;
use App\Services\Rss\Dto\FeedItems;

final class FeedChangesDetector
{
    public function hasChanged(RssFeed $rssFeed, FeedItems $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $latestGuid = $items[0]['guid'] ?? null;

        return $rssFeed->last_rss_hash !== $latestGuid;
    }

    /**
     * @return FeedItems
     */
    public function getNewItems(RssFeed $rssFeed, FeedItems $items): FeedItems
    {
        if (empty($rssFeed->last_rss_hash)) {
            return $items;
        }

        $newItems = [];
        foreach ($items as $item) {
            if ($item['guid'] === $rssFeed->last_rss_hash) {
                break;
            }
            $newItems[] = $item;
        }

        return new FeedItems($newItems);
    }
}
