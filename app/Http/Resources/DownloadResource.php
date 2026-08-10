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
            'release' => new ReleaseResource($this->whenLoaded('release')),
            'items' => DownloadItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
