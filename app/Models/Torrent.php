<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Torrent extends Model
{
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

    protected $casts = [
        'episodes' => 'array',
    ];

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
        return !empty(array_intersect($this->episodes, $episodeNumbers));
    }
}
