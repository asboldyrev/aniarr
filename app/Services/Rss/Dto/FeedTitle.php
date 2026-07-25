<?php

namespace App\Services\Rss\Dto;

class FeedTitle
{
    public function __construct(
        public readonly string $title,
        public readonly string $codec,
        public readonly array $episodes,
        public readonly string $quality,
    ) {}
}
