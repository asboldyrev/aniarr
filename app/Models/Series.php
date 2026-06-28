<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Model
{
    use HasFactory;

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

    protected $casts = [
        'status' => Status::class,
        'active_download_is_hevc' => 'boolean',
        'sonarr_connected' => 'boolean',
        'has_hevc' => 'boolean',
        'has_avc' => 'boolean',
        'upgrade_to_hevc' => 'boolean',
    ];

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function torrents(): HasMany
    {
        return $this->hasMany(Torrent::class);
    }

    public function hevcTorrents(): HasMany
    {
        return $this->hasMany(Torrent::class)->where('codec', 'HEVC');
    }

    public function avcTorrents(): HasMany
    {
        return $this->hasMany(Torrent::class)->where('codec', 'AVC');
    }
}
