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
 * @property int $rss_feed_id
 * @property string $guid
 * @property string|null $torrent_id
 * @property string|null $release_id
 * @property string $title
 * @property string $torrent_url
 * @property Codec $codec
 * @property string|null $quality
 * @property int $first_episode
 * @property int $last_episode
 * @property int|null $size_bytes
 * @property Carbon|null $published_at
 * @property bool $is_current
 * @property Carbon|null $last_seen_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read RssFeed $rssFeed
 * @property-read Collection<int, Download> $downloads
 */
class Release extends Model
{
    protected $fillable = [
        'rss_feed_id',
        'guid',
        'torrent_id',
        'release_id',
        'title',
        'torrent_url',
        'codec',
        'quality',
        'first_episode',
        'last_episode',
        'size_bytes',
        'published_at',
        'is_current',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'rss_feed_id' => 'integer',
            'codec' => Codec::class,
            'first_episode' => 'integer',
            'last_episode' => 'integer',
            'size_bytes' => 'integer',
            'published_at' => 'datetime',
            'is_current' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function rssFeed(): BelongsTo
    {
        return $this->belongsTo(RssFeed::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }
}
