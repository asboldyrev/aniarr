<?php

namespace Tests\Feature\Jobs;

use App\Integrations\Sonarr\Dto\SonarrSeries;
use App\Integrations\Sonarr\SonarrClient;
use App\Jobs\AddSeriesToSonarrJob;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AddSeriesToSonarrJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_series_and_stores_sonarr_id(): void
    {
        $series = Series::query()->create([
            'title' => 'Test series',
            'thetvdb_id' => 411893,
            'thetvdb_slug' => 'test-series',
            'monitored' => true,
        ]);

        $lookup = $this->sonarrSeries(id: 0, tvdbId: 411893);
        $added = $this->sonarrSeries(id: 123, tvdbId: 411893);

        $client = Mockery::mock(SonarrClient::class);
        $client->shouldReceive('testConnection')->once()->andReturnTrue();
        $client->shouldReceive('getSeriesByTvdbId')->once()->with(411893)->andReturnNull();
        $client->shouldReceive('findByTvdbId')->once()->with(411893)->andReturn($lookup);
        $client->shouldReceive('getRootFolders')->once()->andReturn([
            (object) ['path' => '/media/series'],
        ]);
        $client->shouldReceive('getQualityProfiles')->once()->andReturn([
            ['id' => 1, 'name' => 'HD'],
        ]);
        $client->shouldReceive('addSeriesFromLookup')
            ->once()
            ->with($lookup, '/media/series', 1)
            ->andReturn($added);

        (new AddSeriesToSonarrJob($series->id))->handle($client);

        $this->assertSame(123, $series->fresh()->sonarr_id);
    }

    public function test_it_fails_when_sonarr_is_unavailable(): void
    {
        $series = Series::query()->create([
            'title' => 'Test series',
            'thetvdb_id' => 411893,
            'thetvdb_slug' => 'test-series',
            'monitored' => true,
        ]);

        $client = Mockery::mock(SonarrClient::class);
        $client->shouldReceive('testConnection')->once()->andReturnFalse();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sonarr недоступен.');

        (new AddSeriesToSonarrJob($series->id))->handle($client);
    }

    private function sonarrSeries(int $id, int $tvdbId): SonarrSeries
    {
        return SonarrSeries::makeFromResponse([
            'id' => $id,
            'tvdbId' => $tvdbId,
            'title' => 'Test series',
            'sortTitle' => 'test series',
            'status' => 'continuing',
            'monitored' => true,
            'seasonFolder' => true,
            'seriesType' => 'standard',
            'titleSlug' => 'test-series',
        ]);
    }
}
