<?php

namespace App\Actions\Downloads;

use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Jobs\PrepareDownloadJob;
use App\Models\Download;
use App\Models\Season;
use App\Services\Downloads\Dto\DownloadPlan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateDownloadFromPlanAction
{
    private const ACTIVE_STATUSES = [
        DownloadStatus::PENDING,
        DownloadStatus::PREPARING,
        DownloadStatus::DOWNLOADING,
        DownloadStatus::IMPORTING,
    ];

    public function execute(
        Season $season,
        DownloadPlan $plan,
        DownloadTrigger $trigger = DownloadTrigger::AUTOMATIC,
    ): ?Download {
        return DB::transaction(function () use ($season, $plan, $trigger): ?Download {
            /** @var Season|null $lockedSeason */
            $lockedSeason = Season::query()
                ->lockForUpdate()
                ->find($season->id);

            if ($lockedSeason === null) {
                return null;
            }

            $hasActiveDownload = $lockedSeason->downloads()
                ->whereIn('status', array_map(fn (DownloadStatus $status) => $status->value, self::ACTIVE_STATUSES))
                ->exists();

            if ($hasActiveDownload) {
                return null;
            }

            $release = $plan->release->fresh('rssFeed');
            if (
                $release === null
                || ! $release->is_current
                || $release->rssFeed->season_id !== $lockedSeason->id
            ) {
                throw new InvalidArgumentException('Release does not belong to the target season or is no longer current.');
            }

            if ($plan->items === []) {
                return null;
            }

            foreach ($plan->items as $item) {
                if ($item->episode->season_id !== $lockedSeason->id) {
                    throw new InvalidArgumentException('Download plan contains an episode from another season.');
                }

                if (
                    $item->episode->episode_number < $release->first_episode
                    || $item->episode->episode_number > $release->last_episode
                ) {
                    throw new InvalidArgumentException('Download plan contains an episode not covered by release.');
                }
            }

            /** @var Download $download */
            $download = $lockedSeason->downloads()->create([
                'release_id' => $release->id,
                'trigger' => $trigger,
                'status' => DownloadStatus::PENDING,
                'progress' => 0,
                'queued_at' => now(),
            ]);

            $download->update([
                'qbit_tag' => 'aniarr-download-'.$download->id,
            ]);

            foreach ($plan->items as $item) {
                $download->items()->create([
                    'episode_id' => $item->episode->id,
                    'reason' => $item->reason,
                ]);
            }

            PrepareDownloadJob::dispatch($download->id)
                ->onQueue('downloads')
                ->afterCommit();

            return $download->load(['release', 'items.episode']);
        });
    }
}
