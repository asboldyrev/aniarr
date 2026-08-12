<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RealtimeChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $resource,
        public readonly string $action,
        public readonly ?int $id = null,
        public readonly ?int $seriesId = null,
        public readonly ?int $seasonId = null,
        public readonly ?int $downloadId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('aniarr')];
    }

    public function broadcastAs(): string
    {
        return 'realtime.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'resource' => $this->resource,
            'action' => $this->action,
            'id' => $this->id,
            'seriesId' => $this->seriesId,
            'seasonId' => $this->seasonId,
            'downloadId' => $this->downloadId,
        ];
    }
}
