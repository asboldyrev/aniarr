<?php

namespace Tests\Feature\Actions\Downloads;

use App\Actions\Downloads\ForceReleaseDownloadAction;
use App\Enums\Codec;
use App\Enums\DownloadReason;
use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Exceptions\ActiveDownloadExists;
use App\Jobs\PrepareDownloadJob;
use App\Models\Download;
use App\Models\Episode;
use App\Models\Release;
use App\Models\Season;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class ForceReleaseDownloadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_manual_refresh_for_all_covered_episodes(): void
    {
        Queue::fake();

        [$season, $release, $episodes] = $this->domain();

        $download = app(ForceReleaseDownloadAction::class)->execute($release);

        $this->assertSame(DownloadTrigger::MANUAL, $download->trigger);
        $this->assertSame(DownloadStatus::PENDING, $download->status);
        $this->assertSame($release->id, $download->release_id);
        $this->assertCount(3, $download->items);

        foreach ($episodes as $episode) {
            $this->assertDatabaseHas('download_items', [
                'download_id' => $download->id,
                'episode_id' => $episode->id,
                'reason' => DownloadReason::REFRESH->value,
            ]);
        }

        Queue::assertPushed(
            PrepareDownloadJob::class,
            fn (PrepareDownloadJob $job): bool => $job->downloadId === $download->id,
        );
    }

    public function test_it_can_download_only_selected_episodes(): void
    {
        Queue::fake();

        [, $release, $episodes] = $this->domain();

        $download = app(ForceReleaseDownloadAction::class)->execute(
            $release,
            [$episodes[1]->id],
        );

        $this->assertCount(1, $download->items);
        $this->assertSame($episodes[1]->id, $download->items->first()->episode_id);
        $this->assertSame(DownloadReason::REFRESH, $download->items->first()->reason);
    }

    public function test_historical_release_can_be_downloaded_manually(): void
    {
        Queue::fake();

        [, $release] = $this->domain();
        $release->update(['is_current' => false]);

        $download = app(ForceReleaseDownloadAction::class)->execute($release->fresh());

        $this->assertSame(DownloadTrigger::MANUAL, $download->trigger);
        $this->assertSame($release->id, $download->release_id);
    }

    public function test_existing_file_can_be_refreshed_regardless_of_codec(): void
    {
        Queue::fake();

        [, $release, $episodes] = $this->domain();
        $episodes[0]->update([
            'has_file' => true,
            'file_codec' => Codec::HEVC,
        ]);

        $download = app(ForceReleaseDownloadAction::class)->execute(
            $release,
            [$episodes[0]->id],
        );

        $this->assertSame(DownloadReason::REFRESH, $download->items->first()->reason);
    }

    public function test_active_download_blocks_manual_download(): void
    {
        Queue::fake();

        [$season, $release] = $this->domain();

        Download::query()->create([
            'season_id' => $season->id,
            'release_id' => $release->id,
            'trigger' => DownloadTrigger::AUTOMATIC,
            'status' => DownloadStatus::DOWNLOADING,
        ]);

        $this->expectException(ActiveDownloadExists::class);

        app(ForceReleaseDownloadAction::class)->execute($release);
    }

    public function test_episode_outside_release_range_is_rejected(): void
    {
        Queue::fake();

        [$season, $release] = $this->domain();

        $episode = $season->episodes()->create([
            'sonarr_id' => 999,
            'episode_number' => 13,
            'title' => 'Episode 13',
            'has_file' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ForceReleaseDownloadAction::class)->execute($release, [$episode->id]);
    }

    public function test_episode_from_another_season_is_rejected(): void
    {
        Queue::fake();

        [, $release] = $this->domain();
        [$otherSeason] = $this->domain(tvdbId: 202);

        /** @var Episode $otherEpisode */
        $otherEpisode = $otherSeason->episodes()->firstOrFail();

        $this->expectException(InvalidArgumentException::class);

        app(ForceReleaseDownloadAction::class)->execute($release, [$otherEpisode->id]);
    }

    /** @return array{Season, Release, array<int, Episode>} */
    private function domain(int $tvdbId = 101): array
    {
        $series = Series::query()->create([
            'title' => 'Series '.$tvdbId,
            'thetvdb_id' => $tvdbId,
            'thetvdb_slug' => 'series-'.$tvdbId,
            'monitored' => true,
        ]);

        $season = $series->seasons()->create([
            'number' => 1,
            'monitored' => true,
        ]);

        $feed = $season->rssFeed()->create([
            'rss_url' => 'https://example.test/'.$tvdbId.'.xml',
            'enabled' => true,
        ]);

        $release = $feed->releases()->create([
            'guid' => 'release-'.$tvdbId,
            'title' => 'Release',
            'torrent_url' => 'https://example.test/'.$tvdbId.'.torrent',
            'codec' => Codec::HEVC,
            'first_episode' => 1,
            'last_episode' => 3,
            'is_current' => true,
            'last_seen_at' => now(),
        ]);

        $episodes = [];
        foreach ([1, 2, 3] as $number) {
            $episodes[] = $season->episodes()->create([
                'sonarr_id' => ($tvdbId * 10) + $number,
                'episode_number' => $number,
                'title' => 'Episode '.$number,
                'has_file' => false,
            ]);
        }

        return [$season, $release, $episodes];
    }
}
