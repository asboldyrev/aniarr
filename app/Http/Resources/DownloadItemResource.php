<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DownloadItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'episodeId' => $this->episode_id,
            'reason' => $this->reason->value,
            'torrentFileIndex' => $this->torrent_file_index,
            'torrentFileName' => $this->torrent_file_name,
            'importedPath' => $this->imported_path,
            'episode' => new EpisodeResource($this->whenLoaded('episode')),
        ];
    }
}
