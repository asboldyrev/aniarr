<?php

namespace App\Services\Rss\Dto;

class FeedItems
{
    public function __construct(
        public readonly array $items,
    ) {}
}
