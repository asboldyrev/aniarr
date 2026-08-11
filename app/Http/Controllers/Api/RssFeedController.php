<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRssFeedRequest;
use App\Http\Resources\RssFeedResource;
use App\Jobs\SyncRssFeedJob;
use App\Models\RssFeed;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class RssFeedController extends Controller
{
    public function store(UpdateRssFeedRequest $request, Season $season): RssFeedResource
    {
        $season->loadMissing('series');

        $rssFeed = $season->rssFeed()->updateOrCreate([], [
            'rss_url' => $request->string('rss_url')->toString(),
            'enabled' => $request->boolean('enabled'),
            'last_rss_hash' => null,
            'last_rss_check' => null,
            'last_rss_success_at' => null,
            'last_error_at' => null,
            'last_error' => null,
        ]);

        if ($rssFeed->enabled && $season->monitored && $season->series->monitored) {
            SyncRssFeedJob::dispatch($rssFeed->id);
        }

        return new RssFeedResource($rssFeed->load('releases'));
    }

    public function update(UpdateRssFeedRequest $request, RssFeed $rssFeed): RssFeedResource
    {
        $rssFeed->loadMissing('season.series');

        $rssUrl = $request->string('rss_url')->toString();
        $enabled = $request->boolean('enabled');
        $urlChanged = $rssUrl !== $rssFeed->rss_url;

        $attributes = [
            'rss_url' => $rssUrl,
            'enabled' => $enabled,
        ];

        if ($urlChanged) {
            $rssFeed->releases()->update(['is_current' => false]);

            $attributes = array_merge($attributes, [
                'last_rss_hash' => null,
                'last_rss_check' => null,
                'last_rss_success_at' => null,
                'last_error_at' => null,
                'last_error' => null,
            ]);
        }

        $rssFeed->update($attributes);

        if (
            $enabled
            && $rssFeed->season->monitored
            && $rssFeed->season->series->monitored
        ) {
            SyncRssFeedJob::dispatch($rssFeed->id);
        }

        return new RssFeedResource($rssFeed->fresh()->load('releases'));
    }

    public function destroy(RssFeed $rssFeed): Response|JsonResponse
    {
        $hasDownloadHistory = $rssFeed->releases()
            ->whereHas('downloads')
            ->exists();

        if ($hasDownloadHistory) {
            return response()->json([
                'message' => 'Нельзя удалить RSS-ленту, потому что её релизы используются в истории загрузок. Отключите ленту или измените URL.',
            ], 409);
        }

        $rssFeed->releases()->delete();
        $rssFeed->delete();

        return response()->noContent();
    }
}
