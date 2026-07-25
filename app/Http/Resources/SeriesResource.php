<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'thetvdbId' => $this->thetvdb_id,
            'thetvdbSlug' => $this->thetvdb_slug,
            'rssUrl' => $this->rss_url,
            'posterUrl' => $this->poster_path
                ? asset('storage/' . $this->poster_path)
                : $this->poster_url,
            'year' => $this->year,
            'status' => $this->status,
            'progress' => $this->progress,
            'eta' => $this->eta,
            'hasAvc' => $this->has_avc,
            'hasHevc' => $this->has_hevc,
            'lastEpisodes' => $this->last_episodes,
            'lastUpdated' => $this->last_updated?->toIso8601String(),
            'errorMessage' => $this->error_message,
            'sonarrConnected' => $this->sonarr_connected,
        ];
    }

    /**
     * Преобразовать коллекцию в массив
     */
    public static function collection($resource): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return parent::collection($resource);
    }
}
