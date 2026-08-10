<?php

namespace App\Actions\Downloads;

use App\Enums\DownloadReason;
use App\Enums\DownloadTrigger;
use App\Models\Download;
use App\Models\Episode;
use App\Models\Release;
use App\Services\Downloads\Dto\DownloadPlan;
use App\Services\Downloads\Dto\DownloadPlanItem;
use InvalidArgumentException;

final class ForceReleaseDownloadAction
{
    public function __construct(
        private readonly CreateDownloadFromPlanAction $createDownload,
    ) {}

    /**
     * @param  array<int>|null  $episodeIds
     */
    public function execute(Release $release, ?array $episodeIds = null): Download
    {
        $release->loadMissing('rssFeed.season.episodes');

        $season = $release->rssFeed?->season;
        if ($season === null) {
            throw new InvalidArgumentException('Release не связан с сезоном.');
        }

        $coveredEpisodes = $season->episodes
            ->filter(fn (Episode $episode): bool => $episode->episode_number >= $release->first_episode
                && $episode->episode_number <= $release->last_episode)
            ->values();

        if ($episodeIds !== null) {
            $episodeIds = array_values(array_unique(array_map('intval', $episodeIds)));

            $selectedEpisodes = $coveredEpisodes
                ->filter(fn (Episode $episode): bool => in_array($episode->id, $episodeIds, true))
                ->values();

            if ($selectedEpisodes->count() !== count($episodeIds)) {
                throw new InvalidArgumentException(
                    'Один или несколько эпизодов не принадлежат сезону Release или не покрываются его диапазоном.',
                );
            }
        } else {
            $selectedEpisodes = $coveredEpisodes;
        }

        if ($selectedEpisodes->isEmpty()) {
            throw new InvalidArgumentException('Для Release не найдено эпизодов для загрузки.');
        }

        $plan = new DownloadPlan(
            release: $release,
            items: $selectedEpisodes
                ->map(fn (Episode $episode): DownloadPlanItem => new DownloadPlanItem(
                    episode: $episode,
                    reason: DownloadReason::REFRESH,
                ))
                ->all(),
        );

        $download = $this->createDownload->execute(
            season: $season,
            plan: $plan,
            trigger: DownloadTrigger::MANUAL,
        );

        if ($download === null) {
            throw new InvalidArgumentException('Не удалось создать ручную загрузку Release.');
        }

        return $download;
    }
}
