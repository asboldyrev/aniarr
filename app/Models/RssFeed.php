<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $series_id
 * @property int|null $season_number
 * @property string $rss_url
 * @property Carbon|null $last_rss_check
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Series $series
 * @property-read Collection<int, Torrent> $torrents
 */
class RssFeed extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'series_id',
        'season_number',
        'rss_url',
        'last_rss_check',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'series_id' => 'integer',
            'season_number' => 'integer',
            'last_rss_check' => 'datetime',
        ];
    }

    /**
     * Get the series that owns the RSS feed.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /**
     * Get the torrents that came from this RSS feed.
     */
    public function torrents(): HasMany
    {
        return $this->hasMany(Torrent::class);
    }
}
