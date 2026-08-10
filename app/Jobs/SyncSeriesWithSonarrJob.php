<?php

namespace App\Jobs;

use App\Actions\Downloads\CompleteImportedDownloadAction;
use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Events\SeriesUpdated;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Download;
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
        CompleteImportedDownloadAction $completeImportedDownload,
    ): void {
        /** @var Series|null $series */
        $series = Series::find($this->seriesId);
        if ($series === null || ! $series->thetvdb_id) {
            return;
        }

        $logger = app(AniarrLogger::class)
            ->forSeries($series)
            ->withSource('sonarr');

        try {
            if (! $sonarrClient->testConnection()) {
                $logger->event(
                    'sonarr.unavailable',
                    '[Sonarr] Синхронизация пропущена: Sonarr недоступен',
                    LogType::WARNING,
                );

                return;
            }

            $seriesInSonarr = $sonarrClient->getSeriesByTvdbId($series->thetvdb_id);
            if ($seriesInSonarr === null) {
                $series->update(['sonarr_id' => null]);
                $logger->event(
                    'sonarr.series_missing',
                    '[Sonarr] Сериал не найден среди добавленных сериалов',
                    LogType::WARNING,
                    ['thetvdb_id' => $series->thetvdb_id],
                );

                return;
            }

            $syncAction->execute($series, $seriesInSonarr, $sonarrClient);

            $this->completePendingImports($series, $completeImportedDownload);

            $logger->event(
                'sonarr.synced',
                '[Sonarr] Синхронизация с Sonarr прошла успешно',
                LogType::INFO,
                ['sonarr_id' => $seriesInSonarr->id],
            );

            event(new SeriesUpdated($series->fresh()));

            foreach ($series->seasons()->pluck('id') as $seasonId) {
                PlanSeasonDownloadsJob::dispatch((int) $seasonId)
                    ->onQueue('downloads');
            }
        } catch (Throwable $e) {
            $logger->exception($e, event: 'sonarr.sync_failed');

            throw $e;
        }
    }

    private function completePendingImports(
        Series $series,
        CompleteImportedDownloadAction $completeImportedDownload,
    ): void {
        Download::query()
            ->whereHas('season', fn ($query) => $query->where('series_id', $series->id))
            ->where('status', DownloadStatus::IMPORTING->value)
            ->whereNotNull('imported_at')
            ->each(fn (Download $download) => $completeImportedDownload->execute($download));
    }
}
