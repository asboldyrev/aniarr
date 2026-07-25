<?php

namespace App\Jobs;

use App\Models\Series;
use App\Services\AniarrLogger;
use App\Services\SonarrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddSeriesToSonarrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $seriesId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SonarrService $sonarrService): void
    {
        /** @var Series $series */
        $series = Series::find($this->seriesId);
        if (!$series || !$series->thetvdb_id) {
            return;
        }

        if (!$sonarrService->testConnection() || !$series->thetvdb_id) {
            $series->update(['sonarr_connected' => false, 'last_updated' => now()]);
            return;
        }

        $seriesInSonarr = $this->addSeriesToSonarr($sonarrService, $series);
        if ($seriesInSonarr === null) {
            $series->update(['sonarr_connected' => false, 'last_updated' => now()]);
            return;
        }

        app(AniarrLogger::class)->success('Сериал добавлен в Sonarr');
    }

    /**
     * Добавляет сериал в Sonarr
     */
    private function addSeriesToSonarr(SonarrService $sonarrService, Series $series): ?array
    {
        $thetvdbId = $series->thetvdb_id;
        $lookup = $sonarrService->findByTvdbId($thetvdbId);
        if (!$lookup || !is_array($lookup)) {
            app(AniarrLogger::class)->warning('Сериал не найден в Sonarr');
            return null;
        }

        $rootFolders = $sonarrService->getRootFolders();
        $rootPath = null;
        foreach ($rootFolders as $folder) {
            $path = $folder['path'] ?? null;
            if ($path !== null && $path !== '') {
                $rootPath = $path;
                break;
            }
        }

        if ($rootPath === null) {
            app(AniarrLogger::class)->warning('Не найдены корневые директории в Sonarr');
            return null;
        }

        $qualityProfiles = $sonarrService->getQualityProfiles();
        $qualityProfileId = null;
        foreach ($qualityProfiles as $profile) {
            $id = $profile['id'] ?? null;
            if ($id !== null) {
                $qualityProfileId = (int) $id;
                break;
            }
        }

        if ($qualityProfileId === null) {
            app(AniarrLogger::class)->warning('Не найдены профили качества в Sonarr');
            return null;
        }

        $added = $sonarrService->addSeriesFromLookup($lookup, $rootPath, $qualityProfileId);
        if ($added && is_array($added) && isset($added['id'])) {
            return $added;
        }

        $seriesInSonarr = $sonarrService->findByTvdbId($thetvdbId);

        return $seriesInSonarr;
    }
}
