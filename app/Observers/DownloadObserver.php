<?php

namespace App\Observers;

use App\Events\RealtimeChanged;
use App\Models\Download;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class DownloadObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Download $download): void
    {
        $this->broadcast($download, 'created');
    }

    public function updated(Download $download): void
    {
        $this->broadcast($download, 'updated');
    }

    private function broadcast(Download $download, string $action): void
    {
        $download->loadMissing('season');

        event(new RealtimeChanged(
            resource: 'download',
            action: $action,
            id: $download->id,
            seriesId: $download->season?->series_id,
            seasonId: $download->season_id,
            downloadId: $download->id,
        ));
    }
}
