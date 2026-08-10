<?php

namespace App\Http\Controllers\Api;

use App\Actions\AddSeriesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeriesRequest;
use App\Http\Resources\SeriesResource;
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
        $series = Series::query()->firstOrCreate([
            'thetvdb_id' => $request->integer('thetvdb_id'),
        ], [
            'title' => $request->string('title')->toString(),
            'thetvdb_slug' => $request->string('thetvdb_slug')->toString(),
            'poster_url' => $request->input('poster_url'),
            'year' => $request->integer('year') ?: null,
            'monitored' => true,
        ]);

        $addSeries->execute(
            $request->integer('thetvdb_id'),
            $request->input('rss_feeds', []),
            $series,
        );

        return response()->json(
            new SeriesResource($series->fresh()),
            $series->wasRecentlyCreated ? 201 : 200,
        );
    }
}
