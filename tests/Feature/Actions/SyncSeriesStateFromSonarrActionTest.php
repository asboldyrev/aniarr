<?php

namespace Tests\Feature\Actions;

use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Enums\Codec;
use App\Integrations\Sonarr\Dto\SonarrEpisode;
use App\Integrations\Sonarr\Dto\SonarrSeries;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncSeriesStateFromSonarrActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_seasons_and_actual_episode_file_state(): void
    {
        $series = Series::query()->create([
            'title' => 'Test series',
            'thetvdb_id' => 123,
            'thetvdb_slug' => 'test-series',
        ]);

        $sonarrSeries = SonarrSeries::makeFromResponse([
            'id' => 42,
            'tvdbId' => 123,
            'seasons' => [
                ['seasonNumber' => 1, 'monitored' => true],
            ],
        ]);

        $episodes = [
            SonarrEpisode::makeFromResponse([
                'id' => 1001,
                'seriesId' => 42,
                'seasonNumber' => 1,
                'episodeNumber' => 1,
                'title' => 'Episode 1',
                'hasFile' => true,
                'episodeFileId' => 5001,
                'episodeFile' => [
                    'id' => 5001,
                    'dateAdded' => '2026-08-10T10:00:00Z',
                    'mediaInfo' => ['videoCodec' => 'x265'],
                ],
            ]),
            SonarrEpisode::makeFromResponse([
                'id' => 1002,
                'seriesId' => 42,
                'seasonNumber' => 1,
                'episodeNumber' => 2,
                'title' => 'Episode 2',
                'hasFile' => false,
            ]),
        ];

        $sonarrClient = $this->mock(SonarrClient::class);
        $sonarrClient->shouldReceive('getEpisodes')
            ->once()
            ->with(42)
            ->andReturn($episodes);

        app(SyncSeriesStateFromSonarrAction::class)->execute($series, $sonarrSeries, $sonarrClient);

        $series->refresh();
        $season = $series->seasons()->where('number', 1)->firstOrFail();
        $episodeOne = $season->episodes()->where('episode_number', 1)->firstOrFail();
        $episodeTwo = $season->episodes()->where('episode_number', 2)->firstOrFail();

        $this->assertSame(42, $series->sonarr_id);
        $this->assertNotNull($series->last_sonarr_sync_at);

        $this->assertTrue($episodeOne->has_file);
        $this->assertSame(5001, $episodeOne->sonarr_file_id);
        $this->assertSame(Codec::HEVC, $episodeOne->file_codec);
        $this->assertNotNull($episodeOne->file_date_added);

        $this->assertFalse($episodeTwo->has_file);
        $this->assertNull($episodeTwo->sonarr_file_id);
        $this->assertNull($episodeTwo->file_codec);
        $this->assertNull($episodeTwo->file_date_added);
    }

    public function test_it_clears_file_metadata_when_sonarr_reports_file_as_missing(): void
    {
        $series = Series::query()->create([
            'title' => 'Test series',
            'thetvdb_id' => 123,
            'thetvdb_slug' => 'test-series',
            'sonarr_id' => 42,
        ]);

        $season = $series->seasons()->create(['number' => 1]);
        $season->episodes()->create([
            'sonarr_id' => 1001,
            'episode_number' => 1,
            'title' => 'Episode 1',
            'has_file' => true,
            'sonarr_file_id' => 5001,
            'file_codec' => Codec::AVC,
            'file_date_added' => now(),
        ]);

        $sonarrSeries = SonarrSeries::makeFromResponse([
            'id' => 42,
            'tvdbId' => 123,
            'seasons' => [
                ['seasonNumber' => 1, 'monitored' => true],
            ],
        ]);

        $sonarrClient = $this->mock(SonarrClient::class);
        $sonarrClient->shouldReceive('getEpisodes')
            ->once()
            ->with(42)
            ->andReturn([
                SonarrEpisode::makeFromResponse([
                    'id' => 1001,
                    'seriesId' => 42,
                    'seasonNumber' => 1,
                    'episodeNumber' => 1,
                    'title' => 'Episode 1',
                    'hasFile' => false,
                ]),
            ]);

        app(SyncSeriesStateFromSonarrAction::class)->execute($series, $sonarrSeries, $sonarrClient);

        $episode = $season->episodes()->where('episode_number', 1)->firstOrFail();

        $this->assertFalse($episode->has_file);
        $this->assertNull($episode->sonarr_file_id);
        $this->assertNull($episode->file_codec);
        $this->assertNull($episode->file_date_added);
    }
}
