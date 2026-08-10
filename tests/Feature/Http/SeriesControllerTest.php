<?php

namespace Tests\Feature\Http;

use App\Enums\Codec;
use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_series_with_seasons_and_active_download(): void
    {
        $series = $this->domain();

        $response = $this->getJson('/api/series');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $series->id)
            ->assertJsonPath('data.0.seasons.0.number', 1)
            ->assertJsonPath('data.0.seasons.0.activeDownload.status', 'downloading')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.rssFeeds');
    }

    public function test_show_returns_release_episode_and_download_domain_data(): void
    {
        $series = $this->domain();

        $response = $this->getJson('/api/series/'.$series->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $series->id)
            ->assertJsonPath('data.seasons.0.rssFeed.releases.0.codec', 'hevc')
            ->assertJsonPath('data.seasons.0.episodes.0.fileCodec', 'avc')
            ->assertJsonPath('data.seasons.0.downloads.0.trigger', 'automatic');
    }

    private function domain(): Series
    {
        $series = Series::query()->create([
            'title' => 'API Series',
            'thetvdb_id' => 9001,
            'thetvdb_slug' => 'api-series',
            'sonarr_id' => 91,
            'monitored' => true,
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
            'guid' => 'release-api',
            'title' => 'HEVC 1-12',
            'torrent_url' => 'https://example.test/release.torrent',
            'codec' => Codec::HEVC,
            'first_episode' => 1,
            'last_episode' => 12,
            'is_current' => true,
        ]);

        $episode = $season->episodes()->create([
            'sonarr_id' => 101,
            'episode_number' => 1,
            'title' => 'Episode 1',
            'has_file' => true,
            'file_codec' => Codec::AVC,
        ]);

        $download = $season->downloads()->create([
            'release_id' => $release->id,
            'trigger' => DownloadTrigger::AUTOMATIC,
            'status' => DownloadStatus::DOWNLOADING,
            'progress' => 50,
        ]);

        $download->items()->create([
            'episode_id' => $episode->id,
            'reason' => 'upgrade',
        ]);

        return $series;
    }
}
