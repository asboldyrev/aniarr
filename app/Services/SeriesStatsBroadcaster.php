<?php

namespace App\Services;

use App\Dto\Statistics;
use App\Enums\Status;
use App\Events\StatsUpdated;
use App\Models\Series;

final class SeriesStatsBroadcaster
{
    public static function broadcast(): void
    {
        $totalSeries = Series::count('id');
        $activeDownloads = Series::query()->whereIn('status', [Status::DOWNLOADING_AVC, Status::DOWNLOADING_HEVC])->count();
        $waitingForUpdates = Series::query()->where('status', Status::WAITING)->count();
        $errorsCount = Series::query()->where('status', Status::ERROR)->count();

        $stats = new Statistics(
            $totalSeries,
            $activeDownloads,
            $waitingForUpdates,
            $errorsCount
        );

        broadcast(new StatsUpdated($stats))->toOthers();
    }
}
