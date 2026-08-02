<?php

namespace App\Models;

use App\Enums\Codec;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property int $series_id
 * @property int|null $sonarr_id
 * @property int|null $torrent_id
 * @property int $season_number
 * @property int $episode_number
 * @property string $codec
 * @property Carbon|null $downloaded_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Series $series
 * @property-read Collection<int, EpisodeDownload> $episodeDownloads
 */
class Episode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'series_id',
        'sonarr_id',
        'torrent_id',
        'season_number',
        'episode_number',
        'codec',
        'downloaded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sonarr_id' => 'integer',
            'torrent_id' => 'integer',
            'season_number' => 'integer',
            'episode_number' => 'integer',
            'downloaded_at' => 'datetime',
            'codec' => Codec::class,
        ];
    }

    /**
     * Get the series that owns the episode.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /**
     * Get the torrent that downloaded this episode.
     */
    public function torrent(): BelongsTo
    {
        return $this->belongsTo(Torrent::class);
    }
}
