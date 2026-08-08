<?php

namespace App\Jobs;

use App\Integrations\Sonarr\Dto\RootFolder;
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
class AddSeriesToSonarrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $seriesId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SonarrClient $sonarrClient): void
    {
        /** @var Series $series */
        $series = Series::find($this->seriesId);
        if (! $series || ! $series->thetvdb_id) {
            return;
        }

        if (! $sonarrClient->testConnection() || ! $series->thetvdb_id) {
            $series->update(['sonarr_id' => null, 'last_updated' => now()]);

            return;
        }

        $seriesInSonarr = $this->addSeriesToSonarr($sonarrClient, $series);
        if ($seriesInSonarr === null) {
            $series->update(['sonarr_id' => null, 'last_updated' => now()]);

            return;
        }

        $series->update(['sonarr_id' => $seriesInSonarr->id, 'last_updated' => now()]);

        app(AniarrLogger::class)->info('[Sonarr] Сериал добавлен в Sonarr');
    }

    /**
     * Добавляет сериал в Sonarr.
     *
     * @param  SonarrClient  $sonarrClient  Экземпляр клиента Sonarr
     * @param  Series  $series  Модель сериала
     * @return SonarrSeries|null Данные добавленного сериала или null при ошибке
     */
    private function addSeriesToSonarr(SonarrClient $sonarrClient, Series $series): ?SonarrSeries
    {
        $thetvdbId = $series->thetvdb_id;
        $lookup = $sonarrClient->findByTvdbId($thetvdbId);
        if (! $lookup) {
            app(AniarrLogger::class)->warning('[Sonarr] Сериал не найден в Sonarr');

            return null;
        }

        $rootFolders = $sonarrClient->getRootFolders();
        $rootPath = array_find($rootFolders, fn($folder) => !empty($folder))?->path ?? null;
        if ($rootPath === null) {
            app(AniarrLogger::class)->warning('[Sonarr] Не найдены корневые директории в Sonarr');

            return null;
        }

        $qualityProfiles = $sonarrClient->getQualityProfiles();
        $qualityProfileId = array_find($qualityProfiles, fn($profile) => !empty($profile['id']))['id'] ?? null;
        if ($qualityProfileId === null) {
            app(AniarrLogger::class)->warning('[Sonarr] Не найдены профили качества в Sonarr');

            return null;
        }

        $added = $sonarrClient->addSeriesFromLookup($lookup, $rootPath, $qualityProfileId);
        if (!empty($added)) {
            return $added;
        }

        return null;
    }
}
