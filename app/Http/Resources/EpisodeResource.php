<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EpisodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sonarrId' => $this->sonarr_id,
            'episodeNumber' => $this->episode_number,
            'title' => $this->title,
            'hasFile' => $this->has_file,
            'sonarrFileId' => $this->sonarr_file_id,
            'fileCodec' => $this->file_codec?->value,
            'fileDateAdded' => $this->file_date_added?->toIso8601String(),
        ];
    }
}
