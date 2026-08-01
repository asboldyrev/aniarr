<?php

namespace App\Events;

use App\Models\Series;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeriesUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Series $series
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $seriesId = is_object($this->series) ? $this->series->id : ($this->series['id'] ?? null);

        $channels = [new Channel('series')];

        if ($seriesId) {
            $channels[] = new Channel('series.'.$seriesId);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'series.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->series->id,
            'title' => $this->series->title,
            'thetvdbId' => $this->series->thetvdb_id,
            'thetvdbSlug' => $this->series->thetvdb_slug,
            'posterUrl' => $this->series->poster_path
                ? asset('storage/'.$this->series->poster_path)
                : $this->series->poster_url,
            'year' => $this->series->year,
            'status' => $this->series->status,
            'progress' => $this->series->progress,
            'eta' => $this->series->eta,
            'hasAvc' => $this->series->has_avc,
            'hasHevc' => $this->series->has_hevc,
            'lastEpisodes' => $this->series->last_episodes,
            'lastUpdated' => $this->series->last_updated?->toIso8601String(),
            'errorMessage' => $this->series->error_message,
            'sonarrConnected' => $this->series->sonarr_connected,
            'rssFeeds' => $this->series->rssFeeds->map(function ($feed) {
                return [
                    'id' => $feed->id,
                    'seasonNumber' => $feed->season_number,
                    'rssUrl' => $feed->rss_url,
                    'lastRssHash' => $feed->last_rss_hash,
                    'lastRssCheck' => $feed->last_rss_check?->toIso8601String(),
                ];
            }),
        ];
    }
}
