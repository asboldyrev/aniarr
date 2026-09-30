<?php

namespace App\Models;

use App\Enums\LogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $series_id
 * @property int|null $season_id
 * @property int|null $download_id
 * @property string|null $source
 * @property string|null $event
 * @property LogType $type
 * @property string $message
 * @property array|null $context
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Series|null $series
 * @property-read Season|null $season
 * @property-read Download|null $download
 */
class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id',
        'season_id',
        'download_id',
        'source',
        'event',
        'type',
        'message',
        'context',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'series_id' => 'integer',
            'season_id' => 'integer',
            'download_id' => 'integer',
            'type' => LogType::class,
            'context' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function download(): BelongsTo
    {
        return $this->belongsTo(Download::class);
    }
}
