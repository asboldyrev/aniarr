<?php

namespace App\Dto;

class FeedTitleParsed
{
    public function __construct(
        readonly string $codec,
        readonly array $episodes,
        readonly string $quality,
    ) {
        // throw new \Exception('Not implemented');
    }
}
