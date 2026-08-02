<?php

namespace App\Integrations\Sonarr\Dto;

use Carbon\Carbon;

class SonarrEpisode
{
    public function __construct(
        public readonly int $seriesId,
        public readonly int $tvdbId,
        public readonly int $episodeFileId,
        public readonly int $seasonNumber,
        public readonly int $episodeNumber,
        public readonly string $title,
        public readonly Carbon $airDate,
        public readonly Carbon $airDateUtc,
        public readonly int $runtime,
        public readonly string $overview,
        public readonly EpisodeFile $episodeFile,
        public readonly bool $hasFile,
        public readonly bool $monitored,
        public readonly int $absoluteEpisodeNumber,
        public readonly int $sceneAbsoluteEpisodeNumber,
        public readonly int $sceneEpisodeNumber,
        public readonly int $sceneSeasonNumber,
        public readonly bool $unverifiedSceneNumbering,
        public readonly int $id,
    ) {}

    public static function makeFromResponse(array $response): self
    {
        return new self(
            seriesId: (int) ($response['seriesId'] ?? 0),
            tvdbId: (int) ($response['tvdbId'] ?? 0),
            episodeFileId: (int) ($response['episodeFileId'] ?? 0),
            seasonNumber: (int) ($response['seasonNumber'] ?? 0),
            episodeNumber: (int) ($response['episodeNumber'] ?? 0),
            title: $response['title'] ?? '',
            airDate: Carbon::parse($response['airDate'] ?? now()),
            airDateUtc: Carbon::parse($response['airDateUtc'] ?? now()),
            runtime: (int) ($response['runtime'] ?? 0),
            overview: $response['overview'] ?? '',
            episodeFile: isset($response['episodeFile']) && $response['episodeFile'] ? EpisodeFile::makeFromResponse($response['episodeFile']) : EpisodeFile::makeFromResponse([]),
            hasFile: (bool) ($response['hasFile'] ?? false),
            monitored: (bool) ($response['monitored'] ?? false),
            absoluteEpisodeNumber: (int) ($response['absoluteEpisodeNumber'] ?? 0),
            sceneAbsoluteEpisodeNumber: (int) ($response['sceneAbsoluteEpisodeNumber'] ?? 0),
            sceneEpisodeNumber: (int) ($response['sceneEpisodeNumber'] ?? 0),
            sceneSeasonNumber: (int) ($response['sceneSeasonNumber'] ?? 0),
            unverifiedSceneNumbering: (bool) ($response['unverifiedSceneNumbering'] ?? false),
            id: (int) ($response['id'] ?? 0),
        );
    }
}
