<?php

namespace App\Jobs;

use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Events\SeriesUpdated;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Синхронизирует фактическое состояние сериала с Sonarr.
 */
final class SyncSeriesWithSonarrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $seriesId,
    ) {}

    public function handle(
        SonarrClient $sonarrClient,
        SyncSeriesStateFromSonarrAction $syncAction,
    ): void {
        /** @var Series|null $series */
        $series = Series::find($this->seriesId);
        if ($series === null || ! $series->thetvdb_id) {
            return;
        }

        $logger = app(AniarrLogger::class);
        $logger->setSeries($series->id);

        try {
            if (! $sonarrClient->testConnection()) {
                $logger->warning('[Sonarr] Синхронизация пропущена: Sonarr недоступен');

                return;
            }

            $seriesInSonarr = $sonarrClient->getSeriesByTvdbId($series->thetvdb_id);
            if ($seriesInSonarr === null) {
                $series->update(['sonarr_id' => null]);
                $logger->warning('[Sonarr] Сериал не найден среди добавленных сериалов');

                return;
            }

            $syncAction->execute($series, $seriesInSonarr, $sonarrClient);

            $logger->info('[Sonarr] Синхронизация с Sonarr прошла успешно');
            event(new SeriesUpdated($series->fresh()));
        } catch (Throwable $e) {
            $logger->exception($e);
        } finally {
            $logger->resetSeries();
        }
    }
}
