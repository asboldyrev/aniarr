<?php

namespace App\Jobs;

use App\Actions\Downloads\CreateDownloadFromPlanAction;
use App\Models\Season;
use App\Services\Downloads\SeasonDownloadPlanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PlanSeasonDownloadsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $seasonId,
    ) {}

    public function handle(
        SeasonDownloadPlanner $planner,
        CreateDownloadFromPlanAction $createDownload,
    ): void {
        /** @var Season|null $season */
        $season = Season::query()->find($this->seasonId);
        if ($season === null) {
            return;
        }

        $plan = $planner->plan($season);
        if ($plan === null) {
            return;
        }

        $createDownload->execute($season, $plan);
    }

    public function uniqueId(): string
    {
        return 'plan-season-downloads:' . $this->seasonId;
    }

    public function uniqueFor(): int
    {
        return 60;
    }
}
