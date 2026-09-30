<?php

namespace Tests\Feature\Services\Downloads;

use App\Enums\Codec;
use App\Enums\DownloadReason;
use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Models\Download;
use App\Models\Episode;
use App\Models\Release;
use App\Models\RssFeed;
use App\Models\Season;
use App\Models\Series;
use App\Services\Downloads\SeasonDownloadPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonDownloadPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_episode_prefers_hevc(): void
    {
        $season = $this->season();
        $episode = $this->episode($season, 1);
        $hevc = $this->release($season, Codec::HEVC, 1, 1, 'hevc-1');
        $this->release($season, Codec::AVC, 1, 1, 'avc-1');

        $plan = app(SeasonDownloadPlanner::class)->plan($season);

        $this->assertNotNull($plan);
        $this->assertTrue($plan->release->is($hevc));
        $this->assertCount(1, $plan->items);
        $this->assertTrue($plan->items[0]->episode->is($episode));
        $this->assertSame(DownloadReason::MISSING, $plan->items[0]->reason);
    }

    public function test_missing_episode_falls_back_to_avc(): void
    {
        $season = $this->season();
        $episode = $this->episode($season, 1);
        $avc = $this->release($season, Codec::AVC, 1, 1, 'avc-1');

        $plan = app(SeasonDownloadPlanner::class)->plan($season);

        $this->assertNotNull($plan);
        $this->assertTrue($plan->release->is($avc));
        $this->assertTrue($plan->items[0]->episode->is($episode));
        $this->assertSame(DownloadReason::MISSING, $plan->items[0]->reason);
    }

    public function test_existing_avc_is_upgraded_to_hevc(): void
    {
        $season = $this->season();
        $episode = $this->episode($season, 1, Codec::AVC);
        $hevc = $this->release($season, Codec::HEVC, 1, 1, 'hevc-1');

        $plan = app(SeasonDownloadPlanner::class)->plan($season);

        $this->assertNotNull($plan);
        $this->assertTrue($plan->release->is($hevc));
        $this->assertTrue($plan->items[0]->episode->is($episode));
        $this->assertSame(DownloadReason::UPGRADE, $plan->items[0]->reason);
    }

    public function test_existing_hevc_is_never_downgraded_to_avc(): void
    {
        $season = $this->season();
        $this->episode($season, 1, Codec::HEVC);
        $this->release($season, Codec::AVC, 1, 1, 'avc-1');

        $this->assertNull(app(SeasonDownloadPlanner::class)->plan($season));
    }

    public function test_active_download_blocks_new_plan_for_same_season(): void
    {
        $season = $this->season();
        $this->episode($season, 1, Codec::AVC);
        $release = $this->release($season, Codec::HEVC, 1, 1, 'hevc-1');

        Download::query()->create([
            'season_id' => $season->id,
            'release_id' => $release->id,
            'trigger' => DownloadTrigger::AUTOMATIC,
            'status' => DownloadStatus::DOWNLOADING,
            'progress' => 10,
        ]);

        $this->assertNull(app(SeasonDownloadPlanner::class)->plan($season->fresh()));
    }

    public function test_one_hevc_release_can_include_missing_and_upgrade_items(): void
    {
        $season = $this->season();
        $upgrade = $this->episode($season, 10, Codec::AVC);
        $missing = $this->episode($season, 11);
        $hevc = $this->release($season, Codec::HEVC, 1, 11, 'hevc-11');
        $this->release($season, Codec::AVC, 1, 12, 'avc-12');
        $this->episode($season, 12);

        $plan = app(SeasonDownloadPlanner::class)->plan($season);

        $this->assertNotNull($plan);
        $this->assertTrue($plan->release->is($hevc));
        $this->assertCount(2, $plan->items);

        $items = collect($plan->items)->keyBy(fn ($item) => $item->episode->id);
        $this->assertSame(DownloadReason::UPGRADE, $items[$upgrade->id]->reason);
        $this->assertSame(DownloadReason::MISSING, $items[$missing->id]->reason);
    }

    public function test_avc_missing_is_prioritized_over_hevc_upgrade_only(): void
    {
        $season = $this->season();
        $this->episode($season, 10, Codec::AVC);
        $missing = $this->episode($season, 12);
        $this->release($season, Codec::HEVC, 1, 11, 'hevc-11');
        $avc = $this->release($season, Codec::AVC, 1, 12, 'avc-12');

        $plan = app(SeasonDownloadPlanner::class)->plan($season);

        $this->assertNotNull($plan);
        $this->assertTrue($plan->release->is($avc));
        $this->assertCount(1, $plan->items);
        $this->assertTrue($plan->items[0]->episode->is($missing));
        $this->assertSame(DownloadReason::MISSING, $plan->items[0]->reason);
    }

    public function test_release_must_cover_episode(): void
    {
        $season = $this->season();
        $this->episode($season, 12);
        $this->release($season, Codec::HEVC, 1, 11, 'hevc-11');

        $this->assertNull(app(SeasonDownloadPlanner::class)->plan($season));
    }

    public function test_historical_release_is_not_used_for_planning(): void
    {
        $season = $this->season();
        $this->episode($season, 12);
        $historical = $this->release($season, Codec::HEVC, 1, 12, 'old-hevc');
        $historical->update(['is_current' => false]);
        $this->release($season, Codec::HEVC, 1, 11, 'current-hevc');

        $this->assertNull(app(SeasonDownloadPlanner::class)->plan($season->fresh()));
    }

    public function test_newer_same_codec_release_does_not_refresh_existing_file_automatically(): void
    {
        $season = $this->season();
        $this->episode($season, 1, Codec::HEVC);
        $this->release($season, Codec::HEVC, 1, 1, 'new-hevc');

        $this->assertNull(app(SeasonDownloadPlanner::class)->plan($season));
    }

    public function test_unmonitored_season_is_not_planned(): void
    {
        $season = $this->season();
        $season->update(['monitored' => false]);
        $this->episode($season, 1);
        $this->release($season, Codec::HEVC, 1, 1, 'hevc-1');

        $this->assertNull(app(SeasonDownloadPlanner::class)->plan($season->fresh()));
    }

    private function season(): Season
    {
        $series = Series::query()->create([
            'title' => 'Test series',
            'thetvdb_id' => random_int(100000, 999999),
            'thetvdb_slug' => 'test-series-'.uniqid(),
            'monitored' => true,
        ]);

        $season = $series->seasons()->create([
            'number' => 1,
            'monitored' => true,
        ]);

        $season->rssFeed()->create([
            'rss_url' => 'https://example.com/feed-'.uniqid().'.xml',
            'enabled' => true,
        ]);

        return $season;
    }

    private function episode(Season $season, int $number, ?Codec $codec = null): Episode
    {
        return $season->episodes()->create([
            'sonarr_id' => random_int(100000, 999999),
            'episode_number' => $number,
            'title' => 'Episode '.$number,
            'has_file' => $codec !== null,
            'sonarr_file_id' => $codec !== null ? random_int(100000, 999999) : null,
            'file_codec' => $codec,
            'file_date_added' => $codec !== null ? now() : null,
        ]);
    }

    private function release(
        Season $season,
        Codec $codec,
        int $firstEpisode,
        int $lastEpisode,
        string $guid,
    ): Release {
        /** @var RssFeed $feed */
        $feed = $season->rssFeed;

        return $feed->releases()->create([
            'guid' => $guid,
            'title' => 'Release '.$guid,
            'torrent_url' => 'https://example.com/'.$guid.'.torrent',
            'codec' => $codec,
            'quality' => '1080p',
            'first_episode' => $firstEpisode,
            'last_episode' => $lastEpisode,
            'is_current' => true,
            'published_at' => now(),
            'last_seen_at' => now(),
        ]);
    }
}
