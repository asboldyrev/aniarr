<?php

namespace App\Events;

use App\Http\Resources\SeriesResource;
use App\Models\Series;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeriesUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Series $series,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('series'),
            new Channel('series.'.$this->series->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'series.updated';
    }

    public function broadcastWith(): array
    {
        $series = $this->series->fresh([
            'seasons.rssFeed.releases',
            'seasons.episodes',
            'seasons.downloads.release',
            'seasons.downloads.items.episode',
        ]) ?? $this->series;

        $payload = (new SeriesResource($series))
            ->response()
            ->getData(true);

        return $payload['data'];
    }
}
