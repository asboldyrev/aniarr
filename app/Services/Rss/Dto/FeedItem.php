<?php

namespace App\Services\Rss\Dto;

final readonly class FeedItem
{
    /**
     * @param  array<int>  $episodes
     */
    public function __construct(
        public string $title,
        public string $guid,
        public string $torrentUrl,
        public ?int $torrentId,
        public ?int $releaseId,
        public string $pubDate,
        public int $size,
        public string $codec,
        public array $episodes,
        public string $quality,
    ) {}
}
