<?php

namespace Tests\Feature\Events;

use App\Enums\Codec;
use App\Events\SeriesUpdated;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesUpdatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_new_domain_payload(): void
    {
        $series = Series::query()->create([
            'title' => 'Test Series',
            'thetvdb_id' => 411893,
            'thetvdb_slug' => 'test-series',
            'sonarr_id' => 42,
            'monitored' => true,
            'last_sonarr_sync_at' => now(),
        ]);

        $season = $series->seasons()->create([
            'number' => 1,
            'monitored' => true,
        ]);

        $season->rssFeed()->create([
            'rss_url' => 'https://example.test/feed.xml',
            'enabled' => true,
            'last_rss_hash' => str_repeat('a', 64),
            'last_rss_check' => now(),
        ]);

        $season->episodes()->create([
            'sonarr_id' => 101,
            'episode_number' => 1,
            'title' => 'Episode 1',
            'has_file' => true,
            'sonarr_file_id' => 501,
            'file_codec' => Codec::HEVC,
            'file_date_added' => now(),
        ]);

        $payload = (new SeriesUpdated($series))->broadcastWith();

        $this->assertSame($series->id, $payload['id']);
        $this->assertSame(42, $payload['sonarrId']);
        $this->assertArrayNotHasKey('status', $payload);
        $this->assertArrayNotHasKey('rssFeeds', $payload);
        $this->assertCount(1, $payload['seasons']);
        $this->assertSame(1, $payload['seasons'][0]['number']);
        $this->assertSame(1, $payload['seasons'][0]['episodesCount']);
        $this->assertSame(1, $payload['seasons'][0]['filesCount']);
        $this->assertSame('https://example.test/feed.xml', $payload['seasons'][0]['rssFeed']['rssUrl']);
        $this->assertSame('hevc', $payload['seasons'][0]['episodes'][0]['fileCodec']);
    }
}
