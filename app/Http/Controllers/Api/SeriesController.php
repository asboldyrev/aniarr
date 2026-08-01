<?php

namespace App\Http\Controllers\Api;

use App\Actions\AddSeriesAction;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeriesRequest;
use App\Http\Resources\SeriesResource;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use Illuminate\Http\JsonResponse;

class SeriesController extends Controller
{
    /**
     * Добавить новый сериал
     */
    public function store(StoreSeriesRequest $request, AddSeriesAction $addSeries): JsonResponse
    {
        $series = Series::create([
            'title' => $request->title,
            'thetvdb_id' => $request->thetvdb_id,
            'thetvdb_slug' => $request->thetvdb_slug,
            'poster_url' => null,
            'poster_path' => null,
            'year' => $request->year,
            'status' => Status::WAITING,
            'last_updated' => now(),
        ]);

        app(AniarrLogger::class)->setSeries($series->id);

        $addSeries->execute($request->thetvdb_id, $request->rss_feeds, $series);

        return response()->json(new SeriesResource($series), 201);
    }
}
