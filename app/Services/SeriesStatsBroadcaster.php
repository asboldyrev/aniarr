<?php

namespace App\Services;

use App\Dto\Statistics;
use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Events\StatsUpdated;
use App\Models\ActivityLog;
use App\Models\Download;
use App\Models\Series;

final class SeriesStatsBroadcaster
{
    public static function broadcast(): void
    {
        $totalSeries = Series::query()->count();

        $activeSeriesIds = Download::query()
            ->whereIn('status', DownloadStatus::activeValues())
            ->join('seasons', 'seasons.id', '=', 'downloads.season_id')
            ->distinct()
            ->pluck('seasons.series_id');

        $errorSeriesIds = ActivityLog::query()
            ->where('type', LogType::ERROR->value)
            ->whereNull('resolved_at')
            ->whereNotNull('series_id')
            ->distinct()
            ->pluck('series_id');

        $activeDownloads = $activeSeriesIds->count();
        $errorsCount = $errorSeriesIds->count();

        $waitingForUpdates = Series::query()
            ->where('monitored', true)
            ->whereNotIn('id', $activeSeriesIds)
            ->whereNotIn('id', $errorSeriesIds)
            ->count();

        $stats = new Statistics(
            totalSeries: $totalSeries,
            activeDownloads: $activeDownloads,
            waitingForUpdates: $waitingForUpdates,
            errorsCount: $errorsCount,
        );

        broadcast(new StatsUpdated($stats))->toOthers();
    }
}
