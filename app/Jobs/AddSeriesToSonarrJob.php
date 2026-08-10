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
use RuntimeException;

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
            throw new RuntimeException("Не найден сериал Aniarr для добавления в Sonarr: {$this->seriesId}.");
        }

        $logger = app(AniarrLogger::class);
        $logger->setSeries($series->id);

        try {
            if (! $sonarrClient->testConnection()) {
                throw new RuntimeException('Sonarr недоступен.');
            }

            // Между проверкой в AddSeriesAction и выполнением queued job сериал
            // мог быть добавлен вручную или другим процессом.
            $seriesInSonarr = $sonarrClient->getSeriesByTvdbId($series->thetvdb_id)
                ?? $this->addSeriesToSonarr($sonarrClient, $series);

            if ($seriesInSonarr->id <= 0) {
                throw new RuntimeException('Sonarr вернул некорректный ID после добавления сериала.');
            }

            $series->update(['sonarr_id' => $seriesInSonarr->id]);

            $logger->info('[Sonarr] Сериал добавлен в Sonarr', [
                'sonarr_id' => $seriesInSonarr->id,
                'thetvdb_id' => $series->thetvdb_id,
            ]);
        } finally {
            $logger->resetSeries();
        }
    }

    private function addSeriesToSonarr(SonarrClient $sonarrClient, Series $series): SonarrSeries
    {
        $lookup = $sonarrClient->findByTvdbId($series->thetvdb_id);
        if ($lookup === null) {
            throw new RuntimeException(
                "Sonarr lookup не нашёл сериал с TVDB ID {$series->thetvdb_id}.",
            );
        }

        $rootFolders = $sonarrClient->getRootFolders();
        $rootFolder = array_find($rootFolders, fn ($folder) => ! empty($folder->path));
        if ($rootFolder === null) {
            throw new RuntimeException('В Sonarr не настроена корневая директория.');
        }

        $qualityProfiles = $sonarrClient->getQualityProfiles();
        $qualityProfile = array_find(
            $qualityProfiles,
            fn ($profile) => ! empty($profile['id']),
        );

        if ($qualityProfile === null) {
            throw new RuntimeException('В Sonarr не найден профиль качества.');
        }

        $added = $sonarrClient->addSeriesFromLookup(
            $lookup,
            $rootFolder->path,
            (int) $qualityProfile['id'],
        );

        if ($added === null) {
            throw new RuntimeException('Sonarr не вернул данные добавленного сериала.');
        }

        return $added;
    }
}
