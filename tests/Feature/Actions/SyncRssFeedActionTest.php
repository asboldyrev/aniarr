<?php

namespace Tests\Feature\Actions;

use App\Actions\SyncRssFeedAction;
use App\Models\RssFeed;
use App\Models\Season;
use App\Models\Series;
use App\Services\Rss\Dto\FeedItem;
use App\Services\Rss\Dto\FeedItems;
use App\Services\Rss\FeedFingerprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncRssFeedActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_releases_and_does_not_duplicate_unchanged_feed(): void
    {
        $feed = $this->createFeed();
        $items = new FeedItems([
            $this->feedItem('guid-avc', 'https://example.test/avc.torrent', 'avc', 1, 12),
            $this->feedItem('guid-hevc', 'https://example.test/hevc.torrent', 'hevc', 1, 11),
        ]);

        $action = new SyncRssFeedAction(new FeedFingerprint);

        $this->assertTrue($action->execute($feed, $items));
        $this->assertCount(2, $feed->releases()->get());

        $hash = $feed->fresh()->last_rss_hash;

        $this->assertFalse($action->execute($feed->fresh(), $items));
        $this->assertCount(2, $feed->releases()->get());
        $this->assertSame($hash, $feed->fresh()->last_rss_hash);
    }

    public function test_same_episode_range_with_new_guid_is_saved_as_new_release(): void
    {
        $feed = $this->createFeed();
        $action = new SyncRssFeedAction(new FeedFingerprint);

        $action->execute($feed, new FeedItems([
            $this->feedItem('guid-v1', 'https://example.test/v1.torrent', 'hevc', 1, 12),
        ]));

        $this->assertTrue($action->execute($feed->fresh(), new FeedItems([
            $this->feedItem('guid-v2', 'https://example.test/v2.torrent', 'hevc', 1, 12),
        ])));

        $this->assertCount(2, $feed->releases()->get());
        $this->assertDatabaseHas('releases', [
            'rss_feed_id' => $feed->id,
            'guid' => 'guid-v1',
        ]);
        $this->assertDatabaseHas('releases', [
            'rss_feed_id' => $feed->id,
            'guid' => 'guid-v2',
        ]);
    }

    public function test_same_guid_with_changed_torrent_updates_release_and_hash(): void
    {
        $feed = $this->createFeed();
        $action = new SyncRssFeedAction(new FeedFingerprint);

        $action->execute($feed, new FeedItems([
            $this->feedItem('same-guid', 'https://example.test/v1.torrent', 'avc', 1, 12, size: 100),
        ]));

        $firstHash = $feed->fresh()->last_rss_hash;

        $this->assertTrue($action->execute($feed->fresh(), new FeedItems([
            $this->feedItem('same-guid', 'https://example.test/v2.torrent', 'avc', 1, 12, size: 200),
        ])));

        $this->assertCount(1, $feed->releases()->get());
        $this->assertNotSame($firstHash, $feed->fresh()->last_rss_hash);
        $this->assertDatabaseHas('releases', [
            'rss_feed_id' => $feed->id,
            'guid' => 'same-guid',
            'torrent_url' => 'https://example.test/v2.torrent',
            'size_bytes' => 200,
        ]);
    }

    private function createFeed(): RssFeed
    {
        $series = Series::query()->create([
            'title' => 'Test Series',
            'thetvdb_id' => 123,
            'thetvdb_slug' => 'test-series',
            'monitored' => true,
        ]);

        $season = Season::query()->create([
            'series_id' => $series->id,
            'number' => 1,
            'monitored' => true,
        ]);

        return RssFeed::query()->create([
            'season_id' => $season->id,
            'rss_url' => 'https://example.test/rss',
            'enabled' => true,
        ]);
    }

    private function feedItem(
        string $guid,
        string $torrentUrl,
        string $codec,
        int $firstEpisode,
        int $lastEpisode,
        int $size = 1024,
    ): FeedItem {
        return new FeedItem(
            title: 'Test Series',
            guid: $guid,
            torrentUrl: $torrentUrl,
            torrentId: 10,
            releaseId: 20,
            pubDate: 'Mon, 10 Aug 2026 10:00:00 +0000',
            size: $size,
            codec: $codec,
            episodes: range($firstEpisode, $lastEpisode),
            quality: '1080p',
        );
    }
}
