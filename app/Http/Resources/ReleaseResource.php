<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guid' => $this->guid,
            'torrentId' => $this->torrent_id,
            'releaseId' => $this->release_id,
            'title' => $this->title,
            'torrentUrl' => $this->torrent_url,
            'codec' => $this->codec->value,
            'quality' => $this->quality,
            'firstEpisode' => $this->first_episode,
            'lastEpisode' => $this->last_episode,
            'sizeBytes' => $this->size_bytes,
            'publishedAt' => $this->published_at?->toIso8601String(),
            'isCurrent' => $this->is_current,
            'lastSeenAt' => $this->last_seen_at?->toIso8601String(),
        ];
    }
}
