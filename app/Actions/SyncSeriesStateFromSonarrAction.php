<?php

namespace App\Actions;

use App\Enums\Codec;
use App\Integrations\Sonarr\Dto\EpisodeFile;
use App\Integrations\Sonarr\Dto\SonarrEpisode;
use App\Integrations\Sonarr\Dto\SonarrSeries;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Season;
use App\Models\Series;
use Illuminate\Support\Facades\DB;

/**
 * Синхронизирует фактическое состояние сезонов и эпизодов сериала из Sonarr.
 */
final class SyncSeriesStateFromSonarrAction
{
    public function execute(Series $series, SonarrSeries $sonarrSeries, SonarrClient $sonarrClient): void
    {
        if ($sonarrSeries->id <= 0) {
            return;
        }

        $sonarrEpisodes = $sonarrClient->getEpisodes($sonarrSeries->id);

        DB::transaction(function () use ($series, $sonarrSeries, $sonarrEpisodes): void {
            $series->update([
                'sonarr_id' => $sonarrSeries->id,
            ]);

            $seasons = $this->syncSeasons($series, $sonarrSeries, $sonarrEpisodes);

            /** @var SonarrEpisode $sonarrEpisode */
            foreach ($sonarrEpisodes as $sonarrEpisode) {
                if ($sonarrEpisode->id <= 0) {
                    continue;
                }

                $season = $seasons[$sonarrEpisode->seasonNumber] ?? null;
                if ($season === null) {
                    continue;
                }

                $hasFile = $sonarrEpisode->hasFile && $sonarrEpisode->episodeFileId > 0;

                $season->episodes()->updateOrCreate(
                    ['episode_number' => $sonarrEpisode->episodeNumber],
                    [
                        'sonarr_id' => $sonarrEpisode->id,
                        'title' => $sonarrEpisode->title,
                        'has_file' => $hasFile,
                        'sonarr_file_id' => $hasFile ? $sonarrEpisode->episodeFileId : null,
                        'file_codec' => $hasFile ? $this->getEpisodeFileCodec($sonarrEpisode->episodeFile) : null,
                        'file_date_added' => $hasFile ? $sonarrEpisode->episodeFile->dateAdded : null,
                    ],
                );
            }

            $series->update([
                'last_sonarr_sync_at' => now(),
            ]);
        });
    }

    /**
     * @param  array<SonarrEpisode>  $sonarrEpisodes
     * @return array<int, Season>
     */
    private function syncSeasons(Series $series, SonarrSeries $sonarrSeries, array $sonarrEpisodes): array
    {
        $seasonNumbers = collect($sonarrSeries->seasons)
            ->map(fn (array $season): int => (int) ($season['seasonNumber'] ?? -1))
            ->merge(array_map(fn (SonarrEpisode $episode): int => $episode->seasonNumber, $sonarrEpisodes))
            ->filter(fn (int $seasonNumber): bool => $seasonNumber >= 0)
            ->unique()
            ->sort()
            ->values();

        $result = [];

        foreach ($seasonNumbers as $seasonNumber) {
            /** @var Season $season */
            $season = $series->seasons()->firstOrCreate([
                'number' => $seasonNumber,
            ]);

            $result[$seasonNumber] = $season;
        }

        return $result;
    }

    private function getEpisodeFileCodec(EpisodeFile $episodeFile): ?Codec
    {
        $codec = strtolower(trim($episodeFile->mediaInfo->videoCodec));

        return match ($codec) {
            'hevc', 'h.265', 'h265', 'x265' => Codec::HEVC,
            'avc', 'h.264', 'h264', 'x264' => Codec::AVC,
            default => null,
        };
    }
}
