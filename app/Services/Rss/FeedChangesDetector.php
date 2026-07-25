<?php

namespace App\Services\Rss;

use App\Models\Series;
use App\Services\Rss\Dto\FeedItems;

final class FeedChangesDetector
{
    public function hasChanged(Series $series, FeedItems $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $latestGuid = $items[0]['guid'] ?? null;

        return $series->last_rss_hash !== $latestGuid;
    }

    /**
     * @return FeedItems
     */
    public function getNewItems(Series $series, FeedItems $items): FeedItems
    {
        if (empty($series->last_rss_hash)) {
            return $items;
        }

        $newItems = [];
        foreach ($items as $item) {
            if ($item['guid'] === $series->last_rss_hash) {
                break;
            }
            $newItems[] = $item;
        }

        return new FeedItems($newItems);
    }
}
