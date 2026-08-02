<?php

namespace App\Models;

use App\Enums\Status;
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
 * @property Status $status
 * @property Carbon|null $last_updated
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Episode> $episodes
 * @property-read Collection<int, ActivityLog> $activityLogs
 * @property-read Collection<int, Torrent> $torrents
 * @property-read Collection<int, Torrent> $hevcTorrents
 * @property-read Collection<int, Torrent> $avcTorrents
 * @property-read Collection<int, RssFeed> $rssFeeds
 * @property-read Collection<int, EpisodeDownload> $episodeDownloads
 */
class Series extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово назначать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'sonarr_id',
        'thetvdb_id',
        'thetvdb_slug',
        'poster_url',
        'poster_path',
        'year',
        'status',
        'last_updated',
    ];

    /**
     * Получает атрибуты, которые должны быть приведены.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sonarr_id' => 'integer',
            'thetvdb_id' => 'integer',
            'year' => 'integer',
            'status' => Status::class,
            'last_updated' => 'datetime',
        ];
    }

    /**
     * Получает эпизоды для сериала.
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    /**
     * Получает логи активности для сериала.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Получает торренты для сериала.
     */
    public function torrents(): HasMany
    {
        return $this->hasMany(Torrent::class);
    }

    /**
     * Получает RSS-ленты для сериала.
     */
    public function rssFeeds(): HasMany
    {
        return $this->hasMany(RssFeed::class);
    }

    /**
     * Генерирует тег для qBittorrent на основе ID сериала.
     */
    public function qbitTag(): string
    {
        return 'aniarr-' . $this->id;
    }
}
