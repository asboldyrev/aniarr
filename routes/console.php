<?php

use App\Jobs\SyncRssFeedJob;
use App\Jobs\SyncSeriesWithSonarrJob;
use App\Models\RssFeed;
use App\Models\Series;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    RssFeed::query()
        ->where('enabled', true)
        ->whereHas('season', fn ($query) => $query->where('monitored', true))
        ->whereHas('season.series', fn ($query) => $query->where('monitored', true))
        ->pluck('id')
        ->each(fn (int $rssFeedId) => SyncRssFeedJob::dispatch($rssFeedId));
})->name('SyncRssFeeds')->everyThirtyMinutes();

Schedule::call(function (): void {
    Series::query()
        ->pluck('id')
        ->each(fn (int $seriesId) => SyncSeriesWithSonarrJob::dispatch($seriesId));
})->name('SyncSeriesWithSonarr')->hourly();
