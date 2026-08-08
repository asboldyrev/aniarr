<?php

namespace App\Integrations\QBittorrent\Dto;

class File
{
    public function __construct(
        public int $index,
        public string $name,
        public int $size,
        public int|float $progress,
        public int $priority,
        public ?string $path = '',
        public ?int $episodeNumber = null,
    ) {}

    public static function makeFromResponse(array $response): self
    {
        return new self(
            index: $response['index'],
            name: $response['name'],
            size: $response['size'],
            progress: $response['progress'],
            priority: $response['priority'],
        );
    }
}
