<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $series_id
 * @property string $guid
 * @property string $torrent_url
 * @property string|null $torrent_id
 * @property string $codec
 * @property array $episodes
 * @property int $size
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
        'series_id',
        'guid',
        'torrent_url',
        'torrent_id',
        'codec',
        'episodes',
        'size',
        'downloaded',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'episodes' => 'array',
            'size' => 'integer',
            'downloaded' => 'boolean',
        ];
    }

    /**
     * Get the series that owns the torrent.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /**
     * Get episode range as string like "1-12"
     */
    public function getEpisodeRangeAttribute(): string
    {
        $episodes = $this->episodes;
        if (empty($episodes)) {
            return 'unknown';
        }

        $min = min($episodes);
        $max = max($episodes);

        if ($min === $max) {
            return (string) $min;
        }

        return "{$min}-{$max}";
    }

    /**
     * Check if this torrent has specific episodes
     */
    public function hasEpisodes(array $episodeNumbers): bool
    {
        return ! empty(array_intersect($this->episodes, $episodeNumbers));
    }
}
