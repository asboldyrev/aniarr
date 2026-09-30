<?php

namespace App\Observers;

use App\Events\RealtimeChanged;
use App\Models\ActivityLog;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class ActivityLogObserver implements ShouldHandleEventsAfterCommit
{
    public function created(ActivityLog $activityLog): void
    {
        $this->broadcast($activityLog, 'created');
    }

    public function updated(ActivityLog $activityLog): void
    {
        $this->broadcast($activityLog, 'updated');
    }

    private function broadcast(ActivityLog $activityLog, string $action): void
    {
        event(new RealtimeChanged(
            resource: 'activity',
            action: $action,
            id: $activityLog->id,
            seriesId: $activityLog->series_id,
            seasonId: $activityLog->season_id,
            downloadId: $activityLog->download_id,
        ));
    }
}
