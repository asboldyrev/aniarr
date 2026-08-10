<?php

namespace App\Jobs;

use App\Integrations\Sonarr\Dto\SonarrSeries;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Задача для добавления сериала в Sonarr.
 */
final class AddSeriesToSonarrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $seriesId,
    ) {}

    public function handle(SonarrClient $sonarrClient): void
    {
        /** @var Series|null $series */
        $series = Series::find($this->seriesId);
        if ($series === null || ! $series->thetvdb_id) {
            return;
        }

        $logger = app(AniarrLogger::class);
        $logger->setSeries($series->id);

        try {
            if (! $sonarrClient->testConnection()) {
                $logger->warning('[Sonarr] Добавление сериала пропущено: Sonarr недоступен');

                return;
            }

            $seriesInSonarr = $this->addSeriesToSonarr($sonarrClient, $series);
            if ($seriesInSonarr === null) {
                return;
            }

            $series->update(['sonarr_id' => $seriesInSonarr->id]);

            $logger->info('[Sonarr] Сериал добавлен в Sonarr');
        } finally {
            $logger->resetSeries();
        }
    }

    private function addSeriesToSonarr(SonarrClient $sonarrClient, Series $series): ?SonarrSeries
    {
        $lookup = $sonarrClient->findByTvdbId($series->thetvdb_id);
        if ($lookup === null) {
            app(AniarrLogger::class)->warning('[Sonarr] Сериал не найден через lookup');

            return null;
        }

        $rootFolders = $sonarrClient->getRootFolders();
        $rootPath = array_find($rootFolders, fn ($folder) => ! empty($folder))?->path ?? null;
        if ($rootPath === null) {
            app(AniarrLogger::class)->warning('[Sonarr] Не найдены корневые директории Sonarr');

            return null;
        }

        $qualityProfiles = $sonarrClient->getQualityProfiles();
        $qualityProfileId = array_find($qualityProfiles, fn ($profile) => ! empty($profile['id']))['id'] ?? null;
        if ($qualityProfileId === null) {
            app(AniarrLogger::class)->warning('[Sonarr] Не найдены профили качества Sonarr');

            return null;
        }

        return $sonarrClient->addSeriesFromLookup($lookup, $rootPath, $qualityProfileId);
    }
}
