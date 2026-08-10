<?php

namespace App\Models;

use App\Enums\DownloadReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $download_id
 * @property int $episode_id
 * @property DownloadReason $reason
 * @property int|null $torrent_file_index
 * @property string|null $torrent_file_name
 * @property string|null $imported_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Download $download
 * @property-read Episode $episode
 */
class DownloadItem extends Model
{
    protected $fillable = [
        'download_id',
        'episode_id',
        'reason',
        'torrent_file_index',
        'torrent_file_name',
        'imported_path',
    ];

    protected function casts(): array
    {
        return [
            'download_id' => 'integer',
            'episode_id' => 'integer',
            'reason' => DownloadReason::class,
            'torrent_file_index' => 'integer',
        ];
    }

    public function download(): BelongsTo
    {
        return $this->belongsTo(Download::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
