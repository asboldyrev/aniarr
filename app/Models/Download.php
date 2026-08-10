<?php

namespace App\Models;

use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $season_id
 * @property int $release_id
 * @property DownloadTrigger $trigger
 * @property DownloadStatus $status
 * @property string|null $qbit_hash
 * @property string|null $qbit_tag
 * @property int|null $progress
 * @property int|null $eta_seconds
 * @property Carbon|null $queued_at
 * @property Carbon|null $started_at
 * @property Carbon|null $imported_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 * @property string|null $error_message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Season $season
 * @property-read Release $release
 * @property-read Collection<int, DownloadItem> $items
 * @property-read Collection<int, ActivityLog> $activityLogs
 */
class Download extends Model
{
    protected $fillable = [
        'season_id',
        'release_id',
        'trigger',
        'status',
        'qbit_hash',
        'qbit_tag',
        'progress',
        'eta_seconds',
        'queued_at',
        'started_at',
        'imported_at',
        'completed_at',
        'failed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'season_id' => 'integer',
            'release_id' => 'integer',
            'trigger' => DownloadTrigger::class,
            'status' => DownloadStatus::class,
            'progress' => 'integer',
            'eta_seconds' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'imported_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DownloadItem::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
