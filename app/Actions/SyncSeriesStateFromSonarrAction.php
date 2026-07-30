<?php

namespace App\Actions;

use App\Integrations\Sonarr\SonarrClient;
use App\Models\Episode;
use App\Models\Series;
use Illuminate\Support\Arr;

/**
 * Синхронизирует состояние сериала (эпизоды, кодеки, статус загрузки) из Sonarr.
 */
class SyncSeriesStateFromSonarrAction
{
    /**
     * Синхронизирует эпизоды и метаданные сериала из Sonarr.
     *
     * @param  Series  $series  Модель сериала для обновления
     * @param  array  $sonarrSeries  Сырые данные сериала из Sonarr
     * @param  SonarrClient  $sonarrService  Экземпляр клиента Sonarr
     */
    public function execute(Series $series, array $sonarrSeries, SonarrClient $sonarrService): void
    {
        $sonarrId = (int) ($sonarrSeries['id'] ?? 0);
        if ($sonarrId === 0) {
            return;
        }

        $sonarrEpisodes = $sonarrService->getEpisodes($sonarrId);
        if (empty($sonarrEpisodes)) {
            return;
        }

        $isFirstSync = ! $series->episodes()->exists();

        $seriesHasAvc = $isFirstSync ? false : (bool) $series->has_avc;
        $seriesHasHevc = $isFirstSync ? true : (bool) $series->has_hevc;

        $lastDownloadedEpisode = [0, 0];

        foreach ($sonarrEpisodes as $sonarrEpisode) {
            $seasonNumber = (int) ($sonarrEpisode['seasonNumber'] ?? 0);
            $episodeNumber = (int) ($sonarrEpisode['episodeNumber'] ?? 0);
            $hasFile = ! empty($sonarrEpisode['hasFile']);
            $episodeFile = $sonarrEpisode['episodeFile'] ?? null;

            $hasAvc = $hasFile && ! $isFirstSync && $this->episodeFileHasCodec($episodeFile, 'avc');
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
            } elseif (! $episode->exists) {
                $episode->has_avc = false;
                $episode->has_hevc = false;
            }

            $episode->save();
        }

        [$maxSeason, $maxEpisode] = $lastDownloadedEpisode;

        if (! $maxSeason && ! $maxEpisode) {
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

    /**
     * Определяет, содержит ли файл эпизода определённый видеокодек.
     *
     * @param  array|null  $episodeFile  Данные файла эпизода из Sonarr
     * @param  string  $codec  Целевой кодек ('hevc' или 'avc')
     */
    private function episodeFileHasCodec(?array $episodeFile, string $codec): bool
    {
        $qualityName = Arr::get($episodeFile, 'quality.quality.name');
        $videoCodec = Arr::get($episodeFile, 'mediaInfo.videoCodec');

        $codecStr = strtolower($qualityName.' '.$videoCodec);

        return match (strtolower($codec)) {
            'hevc' => collect(['hevc', 'h.265', 'h265', 'x265'])->contains(fn (string $value) => str_contains($codecStr, $value)),
            'avc' => collect(['avc', 'h.264', 'h264', 'x264'])->contains(fn (string $value) => str_contains($codecStr, $value)),
            default => false
        };
    }

    /**
     * Форматирует номера сезона и эпизодов в стандартизированную строку (например, S01E05 или S01E03-07).
     *
     * @param  int  $season  Номер сезона
     * @param  int  $minEpisode  Минимальный номер эпизода в диапазоне
     * @param  int  $maxEpisode  Максимальный номер эпизода в диапазоне
     * @return string Отформатированная строка эпизода
     */
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
