<?php

namespace App\Integrations\Sonarr\Dto;

class RootFolder
{
    public function __construct(
        public readonly string $path,
        public readonly bool $accessible,
        public readonly int $freeSpace,
        public readonly array $unmappedFolders,
        public readonly int $id,
    ) {}


    public static function makeFromResponse(array $response): self
    {
        return new self(
            path: $response['path'] ?? '',
            accessible: $response['accessible'] ?? false,
            freeSpace: $response['freeSpace'] ?? 0,
            unmappedFolders: $response['unmappedFolders'] ?? [],
            id: $response['id'] ?? 0
        );
    }
}
