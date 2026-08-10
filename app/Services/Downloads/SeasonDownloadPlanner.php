<?php

namespace App\Services\Downloads;

use App\Enums\Codec;
use App\Enums\DownloadReason;
use App\Models\Episode;
use App\Models\Release;
use App\Models\Season;
use App\Services\Downloads\Dto\DownloadPlan;
use App\Services\Downloads\Dto\DownloadPlanItem;
use Illuminate\Support\Collection;

final class SeasonDownloadPlanner
{
    public function plan(Season $season): ?DownloadPlan
    {
        $season->loadMissing([
            'series',
            'episodes',
            'rssFeed.releases',
            'downloads',
        ]);

        if (! $season->monitored || ! $season->series->monitored) {
            return null;
        }

        if ($season->rssFeed === null || ! $season->rssFeed->enabled) {
            return null;
        }

        if ($this->hasActiveDownload($season)) {
            return null;
        }

        $releases = $season->rssFeed->releases
            ->filter(fn (Release $release): bool => $release->is_current)
            ->sortByDesc(fn (Release $release): int => $release->published_at?->getTimestamp() ?? $release->id)
            ->values();

        if ($releases->isEmpty()) {
            return null;
        }

        $hevcPlans = $this->plansForCodec($season->episodes, $releases, Codec::HEVC);
        $avcPlans = $this->plansForCodec($season->episodes, $releases, Codec::AVC);

        $hevcWithMissing = $this->bestPlan(
            $hevcPlans->filter(fn (DownloadPlan $plan): bool => $this->hasReason($plan, DownloadReason::MISSING)),
        );

        if ($hevcWithMissing !== null) {
            return $hevcWithMissing;
        }

        $avcWithMissing = $this->bestPlan(
            $avcPlans->filter(fn (DownloadPlan $plan): bool => $this->hasReason($plan, DownloadReason::MISSING)),
        );

        if ($avcWithMissing !== null) {
            return $avcWithMissing;
        }

        return $this->bestPlan(
            $hevcPlans->filter(fn (DownloadPlan $plan): bool => $this->hasReason($plan, DownloadReason::UPGRADE)),
        );
    }

    private function hasActiveDownload(Season $season): bool
    {
        return $season->downloads->contains(
            fn ($download): bool => $download->status->isActive(),
        );
    }

    /**
     * @param  Collection<int, Episode>  $episodes
     * @param  Collection<int, Release>  $releases
     * @return Collection<int, DownloadPlan>
     */
    private function plansForCodec(Collection $episodes, Collection $releases, Codec $codec): Collection
    {
        return $releases
            ->filter(fn (Release $release): bool => $release->codec === $codec)
            ->map(function (Release $release) use ($episodes, $codec): ?DownloadPlan {
                $items = $episodes
                    ->filter(fn (Episode $episode): bool => $this->releaseCoversEpisode($release, $episode))
                    ->map(function (Episode $episode) use ($codec): ?DownloadPlanItem {
                        if (! $episode->has_file) {
                            return new DownloadPlanItem($episode, DownloadReason::MISSING);
                        }

                        if ($codec === Codec::HEVC && $episode->file_codec === Codec::AVC) {
                            return new DownloadPlanItem($episode, DownloadReason::UPGRADE);
                        }

                        return null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                return $items === [] ? null : new DownloadPlan($release, $items);
            })
            ->filter()
            ->values();
    }

    private function releaseCoversEpisode(Release $release, Episode $episode): bool
    {
        return $episode->episode_number >= $release->first_episode
            && $episode->episode_number <= $release->last_episode;
    }

    private function hasReason(DownloadPlan $plan, DownloadReason $reason): bool
    {
        foreach ($plan->items as $item) {
            if ($item->reason === $reason) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, DownloadPlan>  $plans
     */
    private function bestPlan(Collection $plans): ?DownloadPlan
    {
        return $plans
            ->sort(function (DownloadPlan $left, DownloadPlan $right): int {
                $itemCountComparison = count($right->items) <=> count($left->items);
                if ($itemCountComparison !== 0) {
                    return $itemCountComparison;
                }

                $rangeComparison = $right->release->last_episode <=> $left->release->last_episode;
                if ($rangeComparison !== 0) {
                    return $rangeComparison;
                }

                $leftTimestamp = $left->release->published_at?->getTimestamp() ?? $left->release->id;
                $rightTimestamp = $right->release->published_at?->getTimestamp() ?? $right->release->id;

                return $rightTimestamp <=> $leftTimestamp;
            })
            ->first();
    }
}
