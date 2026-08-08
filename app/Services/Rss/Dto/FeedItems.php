<?php

namespace App\Services\Rss\Dto;

class FeedItems
{
    /**
     * @param array<FeedItem>
     */
    public function __construct(
        public readonly array $items,
    ) {}
}
