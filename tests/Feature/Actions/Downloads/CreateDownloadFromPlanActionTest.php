<?php

namespace Tests\Feature\Actions\Downloads;

use App\Actions\Downloads\CreateDownloadFromPlanAction;
use App\Enums\Codec;
use App\Enums\DownloadReason;
use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Jobs\PrepareDownloadJob;
use App\Models\Download;
use App\Models\Episode;
use App\Models\Release;
use App\Models\Season;
use App\Models\Series;
use App\Services\Downloads\Dto\DownloadPlan;
use App\Services\Downloads\Dto\DownloadPlanItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class CreateDownloadFromPlanActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_download_items_and_dispatches_prepare_job(): void
    {
        Queue::fake();

        [$season, $release, $episode] = $this->domain();
        $plan = new DownloadPlan($release, [
            new DownloadPlanItem($episode, DownloadReason::MISSING),
        ]);

        $download = app(CreateDownloadFromPlanAction::class)->execute($season, $plan);

        $this->assertNotNull($download);
        $this->assertSame(DownloadStatus::PENDING, $download->status);
        $this->assertSame(DownloadTrigger::AUTOMATIC, $download->trigger);
        $this->assertSame('aniarr-download-' . $download->id, $download->qbit_tag);
        $this->assertDatabaseHas('download_items', [
            'download_id' => $download->id,
            'episode_id' => $episode->id,
            'reason' => DownloadReason::MISSING->value,
        ]);

        Queue::assertPushed(
            PrepareDownloadJob::class,
            fn(PrepareDownloadJob $job): bool => $job->downloadId === $download->id,
        );
    }

    public function test_existing_active_download_prevents_duplicate(): void
    {
        Queue::fake();

        [$season, $release, $episode] = $this->domain();

        Download::query()->create([
            'season_id' => $season->id,
            'release_id' => $release->id,
            'trigger' => DownloadTrigger::AUTOMATIC,
            'status' => DownloadStatus::DOWNLOADING,
        ]);

        $plan = new DownloadPlan($release, [
            new DownloadPlanItem($episode, DownloadReason::MISSING),
        ]);

        $download = app(CreateDownloadFromPlanAction::class)->execute($season, $plan);

        $this->assertNull($download);
        $this->assertDatabaseCount('downloads', 1);
        Queue::assertNotPushed(PrepareDownloadJob::class);
    }

    public function test_release_from_another_season_is_rejected(): void
    {
        Queue::fake();

        [$season, , $episode] = $this->domain();
        [$otherSeason, $otherRelease] = $this->domain(tvdbId: 202);

        $this->expectException(InvalidArgumentException::class);

        app(CreateDownloadFromPlanAction::class)->execute(
            $season,
            new DownloadPlan($otherRelease, [
                new DownloadPlanItem($episode, DownloadReason::MISSING),
            ]),
        );

        $this->assertDatabaseMissing('downloads', [
            'season_id' => $otherSeason->id,
            'release_id' => $otherRelease->id,
        ]);
    }

    /** @return array{Season, Release, Episode} */
    private function domain(int $tvdbId = 101): array
    {
        $series = Series::query()->create([
            'title' => 'Series ' . $tvdbId,
            'thetvdb_id' => $tvdbId,
            'thetvdb_slug' => 'series-' . $tvdbId,
            'monitored' => true,
        ]);

        $season = $series->seasons()->create([
            'number' => 1,
            'monitored' => true,
        ]);

        $feed = $season->rssFeed()->create([
            'rss_url' => 'https://example.test/' . $tvdbId . '.xml',
            'enabled' => true,
        ]);

        $release = $feed->releases()->create([
            'guid' => 'release-' . $tvdbId,
            'title' => 'Release',
            'torrent_url' => 'https://example.test/' . $tvdbId . '.torrent',
            'codec' => Codec::HEVC,
            'first_episode' => 1,
            'last_episode' => 12,
            'is_current' => true,
            'last_seen_at' => now(),
        ]);

        $episode = $season->episodes()->create([
            'sonarr_id' => $tvdbId * 10,
            'episode_number' => 1,
            'title' => 'Episode 1',
            'has_file' => false,
        ]);

        return [$season, $release, $episode];
    }
}
