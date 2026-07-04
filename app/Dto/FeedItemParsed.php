<?php

namespace App\Dto;

class FeedItemParsed
{
    public function __construct(
        readonly string $title,
        readonly string $guid,
        readonly string $torrentUrl,
        readonly int $torrentId,
        readonly int $release_id,
        readonly string $pubDate,
        readonly int $size,
        readonly string $codec,
        readonly array $episodes,
        readonly string $quality,
    ) {
        // throw new \Exception('Not implemented');
    }
}
