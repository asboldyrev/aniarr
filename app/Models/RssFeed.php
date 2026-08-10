<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $season_id
 * @property string $rss_url
 * @property bool $enabled
 * @property string|null $last_rss_hash
 * @property Carbon|null $last_rss_check
 * @property Carbon|null $last_rss_success_at
 * @property Carbon|null $last_error_at
 * @property string|null $last_error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Season $season
 * @property-read Collection<int, Release> $releases
 */
class RssFeed extends Model
{
    protected $fillable = [
        'season_id',
        'rss_url',
        'enabled',
        'last_rss_hash',
        'last_rss_check',
        'last_rss_success_at',
        'last_error_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'season_id' => 'integer',
            'enabled' => 'boolean',
            'last_rss_check' => 'datetime',
            'last_rss_success_at' => 'datetime',
            'last_error_at' => 'datetime',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }
}
