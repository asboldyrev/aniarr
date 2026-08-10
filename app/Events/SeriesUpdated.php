<?php

namespace App\Events;

use App\Http\Resources\SeriesResource;
use App\Models\Series;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
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
            'seasons.rssFeed',
            'seasons.episodes',
            'seasons.downloads',
        ]) ?? $this->series;

        return (new SeriesResource($series))->toArray(Request::create('/'));
    }
}
