<?php

namespace Tests\Feature\Http;

use App\Enums\Codec;
use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Jobs\PrepareDownloadJob;
use App\Models\Download;
use App\Models\Release;
use App\Models\Season;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReleaseDownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_manual_download(): void
    {
        Queue::fake();

        [$season, $release, $episodeId] = $this->domain();

        $response = $this->postJson('/api/releases/'.$release->id.'/download', [
            'episode_ids' => [$episodeId],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('seasonId', $season->id)
            ->assertJsonPath('releaseId', $release->id)
            ->assertJsonPath('trigger', 'manual')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('items.0.episodeId', $episodeId)
            ->assertJsonPath('items.0.reason', 'refresh');

        Queue::assertPushed(PrepareDownloadJob::class);
    }

    public function test_active_download_returns_conflict(): void
    {
        Queue::fake();

        [$season, $release] = $this->domain();

        Download::query()->create([
            'season_id' => $season->id,
            'release_id' => $release->id,
            'trigger' => DownloadTrigger::AUTOMATIC,
            'status' => DownloadStatus::DOWNLOADING,
        ]);

        $this->postJson('/api/releases/'.$release->id.'/download')
            ->assertConflict();
    }

    public function test_episode_from_another_season_returns_unprocessable_entity(): void
    {
        Queue::fake();

        [, $release] = $this->domain();
        [, , $otherEpisodeId] = $this->domain(202);

        $this->postJson('/api/releases/'.$release->id.'/download', [
            'episode_ids' => [$otherEpisodeId],
        ])->assertUnprocessable();
    }

    /** @return array{Season, Release, int} */
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
            'last_episode' => 12,
            'is_current' => true,
        ]);

        $episode = $season->episodes()->create([
            'sonarr_id' => $tvdbId * 10,
            'episode_number' => 1,
            'title' => 'Episode 1',
            'has_file' => true,
            'file_codec' => Codec::HEVC,
        ]);

        return [$season, $release, $episode->id];
    }
}
