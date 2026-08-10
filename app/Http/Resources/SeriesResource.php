<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SeriesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'seasons.rssFeed',
            'seasons.episodes',
            'seasons.downloads.release',
        ]);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'thetvdbId' => $this->thetvdb_id,
            'thetvdbSlug' => $this->thetvdb_slug,
            'posterUrl' => $this->poster_path
                ? asset('storage/'.$this->poster_path)
                : $this->poster_url,
            'year' => $this->year,
            'monitored' => $this->monitored,
            'sonarrId' => $this->sonarr_id,
            'lastSonarrSyncAt' => $this->last_sonarr_sync_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'seasons' => SeasonResource::collection(
                $this->seasons->sortBy('number')->values(),
            ),
        ];
    }
}
