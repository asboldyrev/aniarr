<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $sonarr_id
 * @property string $title
 * @property int $series_id
 * @property int $season_number
 * @property int $episode_number
 * @property Carbon|null $downloaded_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Series $series
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
        'sonarr_id',
        'title',
        'series_id',
        'season_number',
        'episode_number',
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
            'season_number' => 'integer',
            'episode_number' => 'integer',
            'downloaded_at' => 'datetime',
        ];
    }

    /**
     * Get the series that owns the episode.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
