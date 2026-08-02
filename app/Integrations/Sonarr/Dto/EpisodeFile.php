<?php

namespace App\Integrations\Sonarr\Dto;

use Carbon\Carbon;

class EpisodeFile
{
    public function __construct(
        public readonly int $seriesId,
        public readonly int $seasonNumber,
        public readonly string $relativePath,
        public readonly string $path,
        public readonly int $size,
        public readonly Carbon $dateAdded,
        public readonly array $languages,
        // [
        //   "id" => 1
        //   "name" => "English"
        // ]
        public readonly array $quality,
        public readonly array $customFormats,
        public readonly int $customFormatScore,
        public readonly int $indexerFlags,
        public readonly string $releaseType,
        public readonly MediaInfo $mediaInfo,
        public readonly bool $qualityCutoffNotMet,
        public readonly int $id,

    ) {}

    public static function makeFromResponse(array $response): self
    {
        return new self(
            seriesId: (int) ($response['seriesId'] ?? 0),
            seasonNumber: (int) ($response['seasonNumber'] ?? 0),
            relativePath: $response['relativePath'] ?? '',
            path: $response['path'] ?? '',
            size: (int) ($response['size'] ?? 0),
            dateAdded: Carbon::parse($response['dateAdded'] ?? now()),
            languages: $response['languages'] ?? [],
            quality: $response['quality'] ?? [],
            customFormats: $response['customFormats'] ?? [],
            customFormatScore: (int) ($response['customFormatScore'] ?? 0),
            indexerFlags: (int) ($response['indexerFlags'] ?? 0),
            releaseType: $response['releaseType'] ?? '',
            mediaInfo: isset($response['mediaInfo']) && $response['mediaInfo'] ? MediaInfo::makeFromResponse($response['mediaInfo']) : MediaInfo::makeFromResponse([]),
            qualityCutoffNotMet: (bool) ($response['qualityCutoffNotMet'] ?? false),
            id: (int) ($response['id'] ?? 0),
        );
    }
}
