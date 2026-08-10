<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RssFeedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rssUrl' => $this->rss_url,
            'enabled' => $this->enabled,
            'lastRssHash' => $this->last_rss_hash,
            'lastRssCheck' => $this->last_rss_check?->toIso8601String(),
            'lastRssSuccessAt' => $this->last_rss_success_at?->toIso8601String(),
            'lastErrorAt' => $this->last_error_at?->toIso8601String(),
            'lastError' => $this->last_error,
            'releases' => ReleaseResource::collection($this->whenLoaded('releases')),
        ];
    }
}
