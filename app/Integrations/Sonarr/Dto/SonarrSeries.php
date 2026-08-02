<?php

namespace App\Integrations\Sonarr\Dto;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Override;

class SonarrSeries implements Arrayable
{
    public function __construct(
        public readonly string $title,
        public readonly string $sortTitle,
        public readonly string $status,
        public readonly bool $ended,
        public readonly string $overview,
        public readonly string $network,
        public readonly string $airTime,
        public readonly array $images,
        // [
        //     "coverType" => "banner"
        //     "url" => "/MediaCover/117/banner.jpg?lastWrite=639040720679677187"
        //     "remoteUrl" => "https://artworks.thetvdb.com/banners/v4/series/414221/banners/63ccd114c3334.jpg"
        // ]
        public readonly array $originalLanguage,
        // [
        //   "id" => 8
        //   "name" => "Japanese"
        // ]
        public readonly string $remotePoster,
        public readonly array $seasons,
        // [
        //   "seasonNumber" => 1
        //   "monitored" => true
        // ]
        public readonly int $year,
        public readonly string $path,
        public readonly int $qualityProfileId,
        public readonly bool $seasonFolder,
        public readonly bool $monitored,
        public readonly string $monitorNewItems,
        public readonly bool $useSceneNumbering,
        public readonly int $runtime,
        public readonly int $tvdbId,
        public readonly int $tvRageId,
        public readonly int $tvMazeId,
        public readonly int $tmdbId,
        public readonly ?Carbon $firstAired,
        public readonly ?Carbon $lastAired,
        public readonly string $seriesType,
        public readonly string $cleanTitle,
        public readonly string $imdbId,
        public readonly string $titleSlug,
        public readonly string $folder,
        public readonly string $certification,
        public readonly array $genres,
        public readonly array $tags,
        public readonly ?Carbon $added,
        public readonly array $ratings,
        public readonly array $statistics,
        public readonly int $languageProfileId,
        public readonly int $id,

    ) {}

    public static function makeFromResponse(array $response): self
    {
        return new self(
            title: $response['title'] ?? '',
            sortTitle: $response['sortTitle'] ?? '',
            status: $response['status'] ?? '',
            ended: (bool) ($response['ended'] ?? false),
            overview: $response['overview'] ?? '',
            network: $response['network'] ?? '',
            airTime: $response['airTime'] ?? '',
            images: $response['images'] ?? [],
            originalLanguage: $response['originalLanguage'] ?? [],
            remotePoster: $response['remotePoster'] ?? '',
            seasons: $response['seasons'] ?? [],
            year: (int) ($response['year'] ?? 0),
            path: $response['path'] ?? '',
            qualityProfileId: (int) ($response['qualityProfileId'] ?? 0),
            seasonFolder: (bool) ($response['seasonFolder'] ?? false),
            monitored: (bool) ($response['monitored'] ?? false),
            monitorNewItems: $response['monitorNewItems'] ?? '',
            useSceneNumbering: (bool) ($response['useSceneNumbering'] ?? false),
            runtime: (int) ($response['runtime'] ?? 0),
            tvdbId: (int) ($response['tvdbId'] ?? 0),
            tvRageId: (int) ($response['tvRageId'] ?? 0),
            tvMazeId: (int) ($response['tvMazeId'] ?? 0),
            tmdbId: (int) ($response['tmdbId'] ?? 0),
            firstAired: isset($response['firstAired']) && $response['firstAired'] ? Carbon::parse($response['firstAired']) : null,
            lastAired: isset($response['lastAired']) && $response['lastAired'] ? Carbon::parse($response['lastAired']) : null,
            seriesType: $response['seriesType'] ?? '',
            cleanTitle: $response['cleanTitle'] ?? '',
            imdbId: $response['imdbId'] ?? '',
            titleSlug: $response['titleSlug'] ?? '',
            folder: $response['folder'] ?? '',
            certification: $response['certification'] ?? '',
            genres: $response['genres'] ?? [],
            tags: $response['tags'] ?? [],
            added: isset($response['added']) && $response['added'] ? Carbon::parse($response['added']) : null,
            ratings: $response['ratings'] ?? [],
            statistics: $response['statistics'] ?? [],
            languageProfileId: (int) ($response['languageProfileId'] ?? 0),
            id: (int) ($response['id'] ?? 0),
        );
    }

    /**
     * @return array<string, int, bool, Carbon, array>
     */
    #[Override]
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
