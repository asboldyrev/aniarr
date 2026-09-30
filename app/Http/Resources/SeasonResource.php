<?php

namespace App\Http\Resources;

use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SeasonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'rssFeed.releases',
            'episodes',
            'downloads.release',
            'downloads.items.episode',
        ]);

        /** @var Download|null $activeDownload */
        $activeDownload = $this->downloads
            ->sortByDesc('id')
            ->first(fn (Download $download): bool => $download->status->isActive());

        return [
            'id' => $this->id,
            'number' => $this->number,
            'monitored' => $this->monitored,
            'episodesCount' => $this->episodes->count(),
            'filesCount' => $this->episodes->where('has_file', true)->count(),
            'activeDownload' => $activeDownload === null ? null : new DownloadResource($activeDownload),
            'rssFeed' => $this->rssFeed === null ? null : new RssFeedResource($this->rssFeed),
            'episodes' => EpisodeResource::collection($this->episodes),
            'downloads' => DownloadResource::collection(
                $this->downloads->sortByDesc('id')->values(),
            ),
        ];
    }
}
