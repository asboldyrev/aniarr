<?php

namespace Tests\Feature\Services\Logging;

use App\Enums\Codec;
use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Enums\LogType;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AniarrLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_scope_writes_series_season_download_source_event_and_context(): void
    {
        [$series, $season, $download] = $this->domain();

        app(AniarrLogger::class)
            ->forDownload($download)
            ->withSource('qbittorrent')
            ->event(
                'download.started',
                'Загрузка запущена',
                LogType::INFO,
                ['hash' => 'abc'],
            );

        $this->assertDatabaseHas('activity_logs', [
            'series_id' => $series->id,
            'season_id' => $season->id,
            'download_id' => $download->id,
            'source' => 'qbittorrent',
            'event' => 'download.started',
            'type' => LogType::INFO->value,
            'message' => 'Загрузка запущена',
        ]);

        $activity = $download->activityLogs()->firstOrFail();
        $this->assertSame(['hash' => 'abc'], $activity->context);
    }

    public function test_scoped_logger_does_not_mutate_singleton_context(): void
    {
        [$series] = $this->domain();
        $base = app(AniarrLogger::class);

        $base->forSeries($series)
            ->withSource('sonarr')
            ->event('sonarr.synced', 'Синхронизировано');

        $base->info('Техническое сообщение без scope');

        $this->assertDatabaseCount('activity_logs', 1);
    }

    private function domain(): array
    {
        $series = Series::query()->create([
            'title' => 'Test',
            'thetvdb_id' => 1001,
            'thetvdb_slug' => 'test',
            'monitored' => true,
        ]);

        $season = $series->seasons()->create(['number' => 1, 'monitored' => true]);
        $feed = $season->rssFeed()->create(['rss_url' => 'https://example.test/rss', 'enabled' => true]);
        $release = $feed->releases()->create([
            'guid' => 'guid-1',
            'title' => 'Release',
            'torrent_url' => 'https://example.test/release.torrent',
            'codec' => Codec::HEVC,
            'first_episode' => 1,
            'last_episode' => 1,
            'is_current' => true,
        ]);

        $download = $season->downloads()->create([
            'release_id' => $release->id,
            'trigger' => DownloadTrigger::AUTOMATIC,
            'status' => DownloadStatus::DOWNLOADING,
        ]);

        return [$series, $season, $download];
    }
}
