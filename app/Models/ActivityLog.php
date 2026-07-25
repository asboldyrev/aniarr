<?php

namespace App\Models;

use App\Enums\LogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $series_id
 * @property string $message
 * @property LogType $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Series $series
 */
class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'series_id',
        'message',
        'type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LogType::class,
        ];
    }

    /**
     * Get the series that owns the activity log.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
