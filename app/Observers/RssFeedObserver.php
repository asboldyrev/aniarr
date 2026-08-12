<?php

namespace App\Observers;

use App\Events\RealtimeChanged;
use App\Models\RssFeed;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class RssFeedObserver implements ShouldHandleEventsAfterCommit
{
    public function created(RssFeed $rssFeed): void
    {
        $this->broadcast($rssFeed, 'created');
    }

    public function updated(RssFeed $rssFeed): void
    {
        $this->broadcast($rssFeed, 'updated');
    }

    public function deleted(RssFeed $rssFeed): void
    {
        $this->broadcast($rssFeed, 'deleted');
    }

    private function broadcast(RssFeed $rssFeed, string $action): void
    {
        $rssFeed->loadMissing('season');

        event(new RealtimeChanged(
            resource: 'series',
            action: $action,
            id: $rssFeed->season?->series_id,
            seriesId: $rssFeed->season?->series_id,
            seasonId: $rssFeed->season_id,
        ));
    }
}
