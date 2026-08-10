<?php

namespace App\Http\Resources;

use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SeasonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Download|null $activeDownload */
        $activeDownload = $this->relationLoaded('downloads')
            ? $this->downloads
                ->sortByDesc('id')
                ->first(fn (Download $download): bool => $download->status->isActive())
            : null;

        return [
            'id' => $this->id,
            'number' => $this->number,
            'monitored' => $this->monitored,
            'episodesCount' => $this->relationLoaded('episodes') ? $this->episodes->count() : null,
            'filesCount' => $this->relationLoaded('episodes') ? $this->episodes->where('has_file', true)->count() : null,
            'activeDownload' => $activeDownload === null ? null : new DownloadResource($activeDownload),
            'rssFeed' => $this->rssFeed === null ? null : new RssFeedResource($this->rssFeed),
            'episodes' => EpisodeResource::collection($this->whenLoaded('episodes')),
            'downloads' => DownloadResource::collection($this->whenLoaded('downloads')),
        ];
    }
}
