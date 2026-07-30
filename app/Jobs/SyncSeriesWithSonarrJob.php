<?php

namespace App\Jobs;

use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Enums\Status;
use App\Events\SeriesUpdated;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use App\Services\SeriesStatsBroadcaster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Задача синхронизации сериала с Sonarr (получение состояния, эпизодов, статуса загрузки).
 */
class SyncSeriesWithSonarrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Создаёт новый экземпляр задачи.
     */
    public function __construct(
        public int $seriesId
    ) {}

    /**
     * Выполняет задачу.
     */
    public function handle(SonarrClient $sonarrService): void
    {
        $series = Series::find($this->seriesId);
        if (! $series) {
            return;
        }

        try {
            $this->runSyncFromSonarrOnly($sonarrService, $series);
        } catch (\Throwable $e) {
            app(AniarrLogger::class)->error('Синхронизация с Sonar завершилась с ошибкой', $e);
            $series->update([
                'status' => Status::ERROR,
                'error_message' => 'Sync with Sonarr: '.$e->getMessage(),
                'sonarr_connected' => false,
                'last_updated' => now(),
            ]);

            SeriesStatsBroadcaster::broadcast();
        }
    }

    /**
     * Только синхронизировать данные из Sonarr в проект (для задачи). Сериал должен уже быть в Sonarr.
     */
    public function runSyncFromSonarrOnly(SonarrClient $sonarrService, Series $series): void
    {
        $sonarrService = app(SonarrClient::class);
        if (! $sonarrService->testConnection() || ! $series->thetvdb_id) {
            $series->update(['sonarr_connected' => false, 'last_updated' => now()]);

            return;
        }

        $seriesInSonarr = $sonarrService->findByTvdbId($series->thetvdb_id);
        if ($seriesInSonarr === null) {
            $series->update(['sonarr_connected' => false, 'last_updated' => now()]);

            return;
        }

        (new SyncSeriesStateFromSonarrAction)->execute($series, $seriesInSonarr, $sonarrService);
        $series->update(['sonarr_connected' => true, 'last_updated' => now()]);

        app(AniarrLogger::class)->success('Синхронизация с Sonarr прошла успешно');

        event(new SeriesUpdated($series->fresh()));

        SeriesStatsBroadcaster::broadcast();
    }
}
