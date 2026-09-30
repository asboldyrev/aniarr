<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seriesId' => $this->series_id,
            'seasonId' => $this->season_id,
            'downloadId' => $this->download_id,
            'source' => $this->source,
            'event' => $this->event,
            'type' => $this->type->value,
            'message' => $this->message,
            'context' => $this->context,
            'resolvedAt' => $this->resolved_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'series' => $this->whenLoaded('series', fn (): ?array => $this->series === null ? null : [
                'id' => $this->series->id,
                'title' => $this->series->title,
            ]),
            'season' => $this->whenLoaded('season', fn (): ?array => $this->season === null ? null : [
                'id' => $this->season->id,
                'number' => $this->season->number,
            ]),
        ];
    }
}
