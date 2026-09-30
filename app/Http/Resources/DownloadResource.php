<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DownloadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seasonId' => $this->season_id,
            'releaseId' => $this->release_id,
            'trigger' => $this->trigger->value,
            'status' => $this->status->value,
            'progress' => $this->progress,
            'etaSeconds' => $this->eta_seconds,
            'qbitHash' => $this->qbit_hash,
            'queuedAt' => $this->queued_at?->toIso8601String(),
            'startedAt' => $this->started_at?->toIso8601String(),
            'importedAt' => $this->imported_at?->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(),
            'failedAt' => $this->failed_at?->toIso8601String(),
            'errorMessage' => $this->error_message,
            'season' => $this->whenLoaded('season', fn (): ?array => $this->season === null ? null : [
                'id' => $this->season->id,
                'number' => $this->season->number,
            ]),
            'series' => $this->when(
                $this->relationLoaded('season') && $this->season?->relationLoaded('series'),
                fn (): ?array => $this->season?->series === null ? null : [
                    'id' => $this->season->series->id,
                    'title' => $this->season->series->title,
                    'posterUrl' => $this->season->series->poster_path
                        ? asset('storage/'.$this->season->series->poster_path)
                        : $this->season->series->poster_url,
                    'year' => $this->season->series->year,
                ],
            ),
            'release' => new ReleaseResource($this->whenLoaded('release')),
            'items' => DownloadItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
