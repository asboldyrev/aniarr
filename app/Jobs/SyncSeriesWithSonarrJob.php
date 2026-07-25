<?php

namespace App\Jobs;

use App\Enums\Status;
use App\Events\SeriesUpdated;
use App\Integrations\SonarrClient;
use App\Models\Episode;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use App\Services\SeriesStatsBroadcaster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class SyncSeriesWithSonarrJob implements ShouldQueue
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
    public function handle(SonarrClient $sonarrService): void
    {
        $series = Series::find($this->seriesId);
        if (!$series) {
            return;
        }

        try {
            $this->runSyncFromSonarrOnly($sonarrService, $series);
        } catch (\Throwable $e) {
            app(AniarrLogger::class)->error('Синхронизация с Sonar завершилась с ошибкой', $e);
            $series->update([
                'status' => Status::ERROR,
                'error_message' => 'Sync with Sonarr: ' . $e->getMessage(),
                'sonarr_connected' => false,
                'last_updated' => now(),
            ]);

            // $rssFeedService->broadcastStats();
        }
    }

    /**
     * Только синхронизировать данные из Sonarr в проект (для задачи). Сериал должен уже быть в Sonarr.
     */
    public function runSyncFromSonarrOnly(SonarrClient $sonarrService, Series $series): void
    {
        $sonarrService = app(SonarrClient::class);
        if (!$sonarrService->testConnection() || !$series->thetvdb_id) {
            $series->update(['sonarr_connected' => false, 'last_updated' => now()]);
            return;
        }

        $seriesInSonarr = $sonarrService->findByTvdbId($series->thetvdb_id);
        if ($seriesInSonarr === null) {
            $series->update(['sonarr_connected' => false, 'last_updated' => now()]);
            return;
        }

        $this->syncSeriesStateFromSonarr($series, $seriesInSonarr, $sonarrService);
        $series->update(['sonarr_connected' => true, 'last_updated' => now()]);

        app(AniarrLogger::class)->success('Синхронизация с Sonarr прошла успешно');

        event(new SeriesUpdated($series->fresh()));

        SeriesStatsBroadcaster::broadcast();
    }

    public function syncSeriesStateFromSonarr(Series $series, array $sonarrSeries, SonarrClient $sonarrService): void
    {
        $sonarrId = (int) ($sonarrSeries['id'] ?? 0);
        if ($sonarrId === 0) {
            return;
        }

        $sonarrEpisodes = $sonarrService->getEpisodes($sonarrId);
        if (empty($sonarrEpisodes)) {
            return;
        }

        $isFirstSync = !$series->episodes()->exists();

        $seriesHasAvc = $isFirstSync ? false : (bool) $series->has_avc;
        $seriesHasHevc = $isFirstSync ? true : (bool) $series->has_hevc;

        $lastDownloadedEpisode = [0, 0];

        foreach ($sonarrEpisodes as $sonarrEpisode) {
            $seasonNumber = (int) ($sonarrEpisode['seasonNumber'] ?? 0);
            $episodeNumber = (int) ($sonarrEpisode['episodeNumber'] ?? 0);
            $hasFile = !empty($sonarrEpisode['hasFile']);
            $episodeFile = $sonarrEpisode['episodeFile'] ?? null;

            $hasAvc = $hasFile && !$isFirstSync && $this->episodeFileHasCodec($episodeFile, 'avc');
            $hasHevc = $hasFile && ($isFirstSync || $this->episodeFileHasCodec($episodeFile, 'hevc'));

            $episode = Episode::query()->firstOrNew([
                'series_id' => $series->id,
                'season_number' => $seasonNumber,
                'episode_number' => $episodeNumber,
            ]);

            $episode->title ??= $sonarrEpisode['title'] ?? null;

            if ($hasFile) {
                $episode->downloaded_at ??= now();

                $episode->has_avc = $hasAvc;
                $episode->has_hevc = $hasHevc;

                $seriesHasAvc = $seriesHasAvc || $hasAvc;
                $seriesHasHevc = $seriesHasHevc || $hasHevc;

                $lastDownloadedEpisode = max(
                    $lastDownloadedEpisode,
                    [$seasonNumber, $episodeNumber],
                );
            } elseif (!$episode->exists) {
                $episode->has_avc = false;
                $episode->has_hevc = false;
            }

            $episode->save();
        }

        [$maxSeason, $maxEpisode] = $lastDownloadedEpisode;

        if (!$maxSeason && !$maxEpisode) {
            return;
        }

        $series->update([
            'last_episodes' => $this->formatLastEpisodes(
                $maxSeason,
                $maxEpisode,
                $maxEpisode,
            ),
            'has_avc' => $seriesHasAvc,
            'has_hevc' => $seriesHasHevc,
            'last_updated' => now(),
        ]);
    }

    private function episodeFileHasCodec(?array $episodeFile, string $codec): bool
    {
        $qualityName = Arr::get($episodeFile, 'quality.quality.name');
        $videoCodec = Arr::get($episodeFile, 'mediaInfo.videoCodec');

        $codecStr = strtolower($qualityName . ' ' . $videoCodec);

        return match (strtolower($codec)) {
            'hevc' => collect(['hevc', 'h.265', 'h265', 'x265'])->contains(fn(string $value) => str_contains($codecStr, $value)),
            'avc' => collect(['avc', 'h.264', 'h264', 'x264'])->contains(fn(string $value) => str_contains($codecStr, $value)),
            default => false
        };
    }

    private function formatLastEpisodes(int $season, int $minEpisode, int $maxEpisode): string
    {
        $seasonStr = str_pad((string) $season, 2, '0', STR_PAD_LEFT);
        if ($minEpisode === $maxEpisode) {
            $episodeStr = str_pad((string) $minEpisode, 2, '0', STR_PAD_LEFT);
            return "S{$seasonStr}E{$episodeStr}";
        }
        $minStr = str_pad((string) $minEpisode, 2, '0', STR_PAD_LEFT);
        $maxStr = str_pad((string) $maxEpisode, 2, '0', STR_PAD_LEFT);
        return "S{$seasonStr}E{$minStr}-{$maxStr}";
    }
}
