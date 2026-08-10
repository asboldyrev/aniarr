<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $series_id
 * @property int $number
 * @property bool $monitored
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Series $series
 * @property-read RssFeed|null $rssFeed
 * @property-read Collection<int, Episode> $episodes
 * @property-read Collection<int, Download> $downloads
 * @property-read Collection<int, ActivityLog> $activityLogs
 */
class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id',
        'number',
        'monitored',
    ];

    protected function casts(): array
    {
        return [
            'series_id' => 'integer',
            'number' => 'integer',
            'monitored' => 'boolean',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function rssFeed(): HasOne
    {
        return $this->hasOne(RssFeed::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
