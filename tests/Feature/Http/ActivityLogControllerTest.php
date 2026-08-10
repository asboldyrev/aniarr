<?php

namespace Tests\Feature\Http;

use App\Enums\LogType;
use App\Models\ActivityLog;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_unresolved_errors_and_warnings(): void
    {
        $series = Series::query()->create([
            'title' => 'Test',
            'thetvdb_id' => 2001,
            'thetvdb_slug' => 'test',
            'monitored' => true,
        ]);

        ActivityLog::query()->create([
            'series_id' => $series->id,
            'source' => 'rss',
            'event' => 'rss.failed',
            'type' => LogType::ERROR,
            'message' => 'RSS failed',
        ]);

        ActivityLog::query()->create([
            'series_id' => $series->id,
            'source' => 'sonarr',
            'event' => 'sonarr.synced',
            'type' => LogType::INFO,
            'message' => 'Synced',
        ]);

        ActivityLog::query()->create([
            'series_id' => $series->id,
            'source' => 'jellyfin',
            'event' => 'jellyfin.unavailable',
            'type' => LogType::WARNING,
            'message' => 'Unavailable',
            'resolved_at' => now(),
        ]);

        $response = $this->getJson('/api/activity?unresolved=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event', 'rss.failed')
            ->assertJsonPath('data.0.type', 'error');
    }

    public function test_activity_can_be_resolved_and_reopened(): void
    {
        $activity = ActivityLog::query()->create([
            'source' => 'rss',
            'event' => 'rss.failed',
            'type' => LogType::ERROR,
            'message' => 'RSS failed',
        ]);

        $this->patchJson("/api/activity/{$activity->id}/resolve")
            ->assertOk()
            ->assertJsonPath('data.id', $activity->id);

        $this->assertNotNull($activity->fresh()->resolved_at);

        $this->patchJson("/api/activity/{$activity->id}/reopen")
            ->assertOk()
            ->assertJsonPath('data.id', $activity->id);

        $this->assertNull($activity->fresh()->resolved_at);
    }

    public function test_index_can_filter_by_source_and_event(): void
    {
        ActivityLog::query()->create([
            'source' => 'rss',
            'event' => 'rss.updated',
            'type' => LogType::INFO,
            'message' => 'RSS updated',
        ]);

        ActivityLog::query()->create([
            'source' => 'sonarr',
            'event' => 'sonarr.synced',
            'type' => LogType::INFO,
            'message' => 'Sonarr synced',
        ]);

        $this->getJson('/api/activity?source=rss&event=rss.updated')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source', 'rss')
            ->assertJsonPath('data.0.event', 'rss.updated');
    }
}
