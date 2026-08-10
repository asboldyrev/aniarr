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
 * @property int $season_id
 * @property int|null $sonarr_id
 * @property int|null $sonarr_file_id
 * @property int $episode_number
 * @property string $title
 * @property bool $has_file
 * @property Codec|null $file_codec
 * @property Carbon|null $file_date_added
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Season $season
 * @property-read Collection<int, DownloadItem> $downloadItems
 */
class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'sonarr_id',
        'sonarr_file_id',
        'episode_number',
        'title',
        'has_file',
        'file_codec',
        'file_date_added',
    ];

    protected function casts(): array
    {
        return [
            'season_id' => 'integer',
            'sonarr_id' => 'integer',
            'sonarr_file_id' => 'integer',
            'episode_number' => 'integer',
            'has_file' => 'boolean',
            'file_codec' => Codec::class,
            'file_date_added' => 'datetime',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function downloadItems(): HasMany
    {
        return $this->hasMany(DownloadItem::class);
    }
}
