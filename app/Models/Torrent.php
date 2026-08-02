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
 * @property int|null $rss_feed_id
 * @property int|null $season_number
 * @property string $torrent_url
 * @property string|null $torrent_id
 * @property string $codec
 * @property array $episodes
 * @property int|null $progress
 * @property int|null $eta
 * @property bool $downloaded
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Series $series
 * @property-read RssFeed|null $rssFeed
 * @property-read Collection<int, EpisodeDownload> $episodeDownloads
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
        'rss_feed_id',
        'season_number',
        'torrent_url',
        'torrent_id',
        'codec',
        'episodes',
        'progress',
        'eta',
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
            'rss_feed_id' => 'integer',
            'season_number' => 'integer',
            'episodes' => 'array',
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

    /**
     * Get the RSS feed that this torrent came from.
     */
    public function rssFeed(): BelongsTo
    {
        return $this->belongsTo(RssFeed::class);
    }
}
