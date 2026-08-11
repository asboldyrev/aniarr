<?php

namespace App\Http\Controllers\Api;

use App\Actions\AddSeriesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeriesRequest;
use App\Http\Requests\UpdateSeriesMonitoringRequest;
use App\Http\Resources\SeriesResource;
use App\Jobs\SyncRssFeedJob;
use App\Models\Series;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SeriesController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $series = Series::query()
            ->with([
                'seasons.rssFeed',
                'seasons.episodes',
                'seasons.downloads.release',
            ])
            ->orderBy('title')
            ->get();

        return SeriesResource::collection($series);
    }

    public function show(Series $series): SeriesResource
    {
        $series->load([
            'seasons.rssFeed.releases',
            'seasons.episodes',
            'seasons.downloads.release',
            'seasons.downloads.items.episode',
        ]);

        return new SeriesResource($series);
    }

    /**
     * Добавить новый сериал.
     */
    public function store(StoreSeriesRequest $request, AddSeriesAction $addSeries): JsonResponse
    {
        $tvdbId = $request->integer('thetvdb_id');
        $alreadyExists = Series::query()
            ->where('thetvdb_id', $tvdbId)
            ->exists();

        $addSeries->execute(
            $tvdbId,
            $request->input('rss_feeds', []),
            monitored: $request->monitored(),
        );

        $series = Series::query()
            ->where('thetvdb_id', $tvdbId)
            ->firstOrFail();

        return response()->json(
            new SeriesResource($series),
            $alreadyExists ? 200 : 201,
        );
    }

    public function updateMonitoring(
        UpdateSeriesMonitoringRequest $request,
        Series $series,
    ): SeriesResource {
        $monitored = $request->monitored();
        $series->update(['monitored' => $monitored]);

        if ($monitored) {
            $series->seasons()
                ->where('monitored', true)
                ->whereHas('rssFeed', fn ($query) => $query->where('enabled', true))
                ->with('rssFeed:id,season_id')
                ->get()
                ->pluck('rssFeed.id')
                ->filter()
                ->each(fn (int $rssFeedId) => SyncRssFeedJob::dispatch($rssFeedId));
        }

        return new SeriesResource($series->fresh());
    }
}
