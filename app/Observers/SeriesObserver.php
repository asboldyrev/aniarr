<?php

namespace App\Observers;

use App\Events\RealtimeChanged;
use App\Models\Series;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class SeriesObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Series $series): void
    {
        $this->broadcast($series, 'created');
    }

    public function updated(Series $series): void
    {
        $this->broadcast($series, 'updated');
    }

    public function deleted(Series $series): void
    {
        $this->broadcast($series, 'deleted');
    }

    private function broadcast(Series $series, string $action): void
    {
        event(new RealtimeChanged(
            resource: 'series',
            action: $action,
            id: $series->id,
            seriesId: $series->id,
        ));
    }
}
