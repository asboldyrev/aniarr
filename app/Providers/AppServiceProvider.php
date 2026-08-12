<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Download;
use App\Models\RssFeed;
use App\Models\Series;
use App\Observers\ActivityLogObserver;
use App\Observers\DownloadObserver;
use App\Observers\RssFeedObserver;
use App\Observers\SeriesObserver;
use App\Services\Logging\AniarrLogger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AniarrLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Series::observe(SeriesObserver::class);
        RssFeed::observe(RssFeedObserver::class);
        Download::observe(DownloadObserver::class);
        ActivityLog::observe(ActivityLogObserver::class);
    }
}
