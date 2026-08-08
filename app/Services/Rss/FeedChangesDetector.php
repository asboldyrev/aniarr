<?php

namespace App\Services\Rss;

use App\Models\RssFeed;
use App\Services\Rss\Dto\FeedItem;
use App\Services\Rss\Dto\FeedItems;

final class FeedChangesDetector
{
    public function hasChanged(RssFeed $rssFeed, FeedItems $items): bool
    {
        if (empty($items)) {
            return false;
        }

        return array_any($items->items, fn(FeedItem $item) => $item->guid != $rssFeed->last_rss_check);
    }

    /**
     * @return FeedItems
     */
    public function getNewItems(RssFeed $rssFeed, FeedItems $items): FeedItems
    {
        if (empty($rssFeed->last_rss_hash)) {
            return $items;
        }

        $newItems = array_filter($items->items, fn(FeedItem $item) => $item->guid !== $rssFeed->last_rss_hash);

        return new FeedItems($newItems);
    }
}
