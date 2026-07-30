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
 * @property int|null $sonarr_id
 * @property string $title
 * @property int $thetvdb_id
 * @property string $thetvdb_slug
 * @property string $rss_url
 * @property string|null $poster_url
 * @property string|null $poster_path
 * @property int|null $year
 * @property Status $status
 * @property int|null $progress
 * @property Carbon|null $eta
 * @property string|null $active_torrent_hash
 * @property string|null $active_download_path
 * @property bool $active_download_is_hevc
 * @property string $codec
 * @property string|null $last_episodes
 * @property string|null $error_message
 * @property bool $sonarr_connected
 * @property Carbon|null $last_rss_check
 * @property string|null $last_rss_hash
 * @property bool $has_hevc
 * @property bool $has_avc
 * @property bool $upgrade_to_hevc
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Episode> $episodes
 * @property-read Collection<int, ActivityLog> $activityLogs
 * @property-read Collection<int, Torrent> $torrents
 * @property-read Collection<int, Torrent> $hevcTorrents
 * @property-read Collection<int, Torrent> $avcTorrents
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
        'sonarr_id',
        'title',
        'thetvdb_id',
        'thetvdb_slug',
        'rss_url',
        'poster_url',
        'poster_path',
        'year',
        'status',
        'progress',
        'eta',
        'active_torrent_hash',
        'active_download_path',
        'active_download_is_hevc',
        'codec',
        'last_episodes',
        'error_message',
        'sonarr_connected',
        'last_rss_check',
        'last_rss_hash',
        'has_hevc',
        'has_avc',
        'upgrade_to_hevc',
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
            'progress' => 'integer',
            'eta' => 'datetime',
            'active_download_is_hevc' => 'boolean',
            'sonarr_connected' => 'boolean',
            'last_rss_check' => 'datetime',
            'has_hevc' => 'boolean',
            'has_avc' => 'boolean',
            'upgrade_to_hevc' => 'boolean',
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
     * Получает HEVC торренты для сериала.
     */
    public function hevcTorrents(): HasMany
    {
        return $this->hasMany(Torrent::class)->where('codec', 'HEVC');
    }

    /**
     * Получает AVC торренты для сериала.
     */
    public function avcTorrents(): HasMany
    {
        return $this->hasMany(Torrent::class)->where('codec', 'AVC');
    }

    /**
     * Генерирует тег для qBittorrent на основе ID сериала.
     */
    public function qbitTag(): string
    {
        return 'aniarr-'.$this->id;
    }
}
