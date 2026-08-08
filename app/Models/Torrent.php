<?php

namespace App\Models;

use App\Enums\Codec;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $series_id
 * @property int|null $season_number
 * @property string $torrent_url
 * @property string|null $torrent_id
 * @property string $codec
 * @property int|null $progress
 * @property int|null $eta
 * @property bool $downloaded
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Series $series
 * @property-read string $episode_range
 */
class Torrent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rss_feed_id',
        'season_number',
        'torrent_url',
        'torrent_id',
        'codec',
        'progress',
        'eta',
        'downloaded',
        'active_torrent_hash'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rss_feed_id' => 'integer',
            'season_number' => 'integer',
            'progress' => 'integer',
            'eta' => 'integer',
            'downloaded' => 'boolean',
            'codec' => Codec::class,
        ];
    }

    /**
     * Get the series that owns the torrent.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    /**
     * Генерирует тег для qBittorrent на основе ID сериала.
     * @deprecated
     */
    public function qbitTag(): string
    {
        return "aniarr-{$this->series_id}-{$this->season_number}";
    }
}
