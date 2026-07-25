<?php

namespace App\Dto;

class FeedParseResult
{
    public function __construct(
        public readonly array $items,
    ) {}
}
