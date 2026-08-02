<?php

namespace App\Actions;

use App\Integrations\Sonarr\Dto\EpisodeFile;
use App\Integrations\Sonarr\Dto\SonarrEpisode;
use App\Integrations\Sonarr\Dto\SonarrSeries;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Episode;
use App\Models\Series;

/**
 * Синхронизирует состояние сериала (эпизоды, кодеки, статус загрузки) из Sonarr.
 */
class SyncSeriesStateFromSonarrAction
{
    /**
     * Синхронизирует эпизоды и метаданные сериала из Sonarr.
     *
     * @param  Series  $series  Модель сериала для обновления
     * @param  SonarrSeries  $sonarrSeries  Сырые данные сериала из Sonarr
     * @param  SonarrClient  $sonarrService  Экземпляр клиента Sonarr
     */
    public function execute(Series $series, SonarrSeries $sonarrSeries, SonarrClient $sonarrService): void
    {
        $sonarrId = (int) ($sonarrSeries->id ?? 0);
        if ($sonarrId === 0) {
            return;
        }

        $sonarrEpisodes = $sonarrService->getEpisodes($sonarrId);
        if (empty($sonarrEpisodes)) {
            return;
        }

        /** @var SonarrEpisode $sonarrEpisode */
        foreach ($sonarrEpisodes as $sonarrEpisode) {
            /** @var Episode $episode */
            $episode = $series->episodes()->firstOrNew([
                'sonarr_id' => $sonarrEpisode->id,
            ], [
                'title' => $sonarrEpisode->title,
                'sonarr_id' => $sonarrEpisode->id,
                'season_number' => $sonarrEpisode->seasonNumber,
                'episode_number' => $sonarrEpisode->episodeNumber,
            ]);

            if ($sonarrEpisode->hasFile) {
                $episode->codec = $this->getEpisodeFileCodec($sonarrEpisode->episodeFile);
                $episode->downloaded_at = $sonarrEpisode->episodeFile->dateAdded;
            }

            $episode->save();
        }
    }

    /**
     * Возвращает кодек эпизода
     *
     * @param  EpisodeFile  $episodeFile  Данные файла эпизода из Sonarr
     */
    private function getEpisodeFileCodec(EpisodeFile $episodeFile): string
    {
        if (in_array($episodeFile->mediaInfo->videoCodec, ['hevc', 'h.265', 'h265', 'x265'])) {
            return 'hevc';
        }
        // ['avc', 'h.264', 'h264', 'x264']
        return 'avc';
    }
}
