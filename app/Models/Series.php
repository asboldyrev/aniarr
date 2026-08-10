<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property int|null $sonarr_id
 * @property int $thetvdb_id
 * @property string $thetvdb_slug
 * @property string|null $poster_url
 * @property string|null $poster_path
 * @property int|null $year
 * @property bool $monitored
 * @property Carbon|null $last_sonarr_sync_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Season> $seasons
 * @property-read Collection<int, ActivityLog> $activityLogs
 */
class Series extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'sonarr_id',
        'thetvdb_id',
        'thetvdb_slug',
        'poster_url',
        'poster_path',
        'year',
        'monitored',
        'last_sonarr_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'sonarr_id' => 'integer',
            'thetvdb_id' => 'integer',
            'year' => 'integer',
            'monitored' => 'boolean',
            'last_sonarr_sync_at' => 'datetime',
        ];
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
