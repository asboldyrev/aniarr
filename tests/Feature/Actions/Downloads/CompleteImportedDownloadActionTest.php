<?php

namespace Tests\Feature\Actions\Downloads;

use App\Actions\Downloads\CompleteImportedDownloadAction;
use App\Enums\Codec;
use App\Enums\DownloadReason;
use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Jobs\CleanupQBitTorrentJob;
use App\Jobs\PlanSeasonDownloadsJob;
use App\Jobs\RefreshJellyfinLibraryJob;
use App\Models\Download;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompleteImportedDownloadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_completes_verified_import_and_dispatches_follow_up_jobs(): void
    {
        Queue::fake();

        [$download, $episode] = $this->domain();
        $episode->update([
            'has_file' => true,
            'sonarr_file_id' => 501,
            'file_codec' => Codec::HEVC,
        ]);

        $completed = app(CompleteImportedDownloadAction::class)->execute($download);

        $this->assertTrue($completed);
        $download->refresh();
        $this->assertSame(DownloadStatus::COMPLETED, $download->status);
        $this->assertSame(100, $download->progress);
        $this->assertNotNull($download->completed_at);

        Queue::assertPushed(CleanupQBitTorrentJob::class);
        Queue::assertPushed(RefreshJellyfinLibraryJob::class);
        Queue::assertPushed(PlanSeasonDownloadsJob::class);
    }

    public function test_it_keeps_download_importing_until_episode_state_is_verified(): void
    {
        Queue::fake();

        [$download] = $this->domain();

        $completed = app(CompleteImportedDownloadAction::class)->execute($download);

        $this->assertFalse($completed);
        $this->assertSame(DownloadStatus::IMPORTING, $download->fresh()->status);
        Queue::assertNothingPushed();
    }

    /** @return array{Download, \App\Models\Episode} */
    private function domain(): array
    {
        $series = Series::query()->create([
            'title' => 'Test Series',
            'thetvdb_id' => 777,
            'thetvdb_slug' => 'test-series',
            'sonarr_id' => 42,
        ]);

        $season = $series->seasons()->create([
            'number' => 1,
            'monitored' => true,
        ]);

        $feed = $season->rssFeed()->create([
            'rss_url' => 'https://example.test/feed.xml',
            'enabled' => true,
        ]);

        $release = $feed->releases()->create([
            'guid' => 'release-1',
            'title' => 'HEVC release',
            'torrent_url' => 'https://example.test/release.torrent',
            'codec' => Codec::HEVC,
            'first_episode' => 1,
            'last_episode' => 1,
            'is_current' => true,
        ]);

        $episode = $season->episodes()->create([
            'sonarr_id' => 101,
            'episode_number' => 1,
            'title' => 'Episode 1',
            'has_file' => false,
        ]);

        $download = Download::query()->create([
            'season_id' => $season->id,
            'release_id' => $release->id,
            'trigger' => DownloadTrigger::AUTOMATIC,
            'status' => DownloadStatus::IMPORTING,
            'progress' => 100,
            'imported_at' => now(),
        ]);

        $download->items()->create([
            'episode_id' => $episode->id,
            'reason' => DownloadReason::MISSING,
        ]);

        return [$download, $episode];
    }
}
