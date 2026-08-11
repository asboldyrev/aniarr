<?php

namespace App\Actions;

use App\Enums\DownloadStatus;
use App\Exceptions\CannotDeleteSeriesWithActiveDownload;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Series;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DeleteSeriesAction
{
    public function execute(Series $series, bool $deleteFromSonarr = false): void
    {
        $hasActiveDownload = $series->seasons()
            ->whereHas('downloads', fn ($query) => $query->whereIn('status', DownloadStatus::activeValues()))
            ->exists();

        if ($hasActiveDownload) {
            throw new CannotDeleteSeriesWithActiveDownload;
        }

        if ($deleteFromSonarr) {
            $sonarrClient = new SonarrClient;
            $sonarrSeriesId = $series->sonarr_id;

            if ($sonarrSeriesId === null) {
                $sonarrSeriesId = $sonarrClient->getSeriesByTvdbId($series->thetvdb_id)?->id;
            }

            if ($sonarrSeriesId !== null && $sonarrSeriesId > 0) {
                $sonarrClient->deleteSeries($sonarrSeriesId, deleteFiles: true);
            }
        }

        $posterPath = $series->poster_path;

        DB::transaction(function () use ($series): void {
            $series->delete();
        });

        if ($posterPath) {
            Storage::disk('public')->delete($posterPath);
        }
    }
}
