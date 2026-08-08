<?php

use App\Jobs\RssCheckAndDownloadJob;
use App\Jobs\SyncSeriesWithSonarrJob;
use App\Models\Series;
use Illuminate\Support\Facades\Schedule;

Schedule::job(RssCheckAndDownloadJob::class)->everyThirtyMinutes();

Schedule::call(function () {
    /** @var Series $series */
    foreach (Series::all() as $series) {
        SyncSeriesWithSonarrJob::dispatch($series->id);
    }
})->name('SyncSeriesWithSonarr')->hourly();
