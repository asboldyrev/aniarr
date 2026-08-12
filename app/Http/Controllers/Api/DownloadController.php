<?php

namespace App\Http\Controllers\Api;

use App\Actions\Downloads\CancelDownloadAction;
use App\Actions\Downloads\RetryDownloadAction;
use App\Enums\DownloadStatus;
use App\Enums\DownloadTrigger;
use App\Http\Controllers\Controller;
use App\Http\Resources\DownloadResource;
use App\Models\Download;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

final class DownloadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_map(
                fn (DownloadStatus $status): string => $status->value,
                DownloadStatus::cases(),
            ))],
            'trigger' => ['nullable', 'string', 'in:'.implode(',', array_map(
                fn (DownloadTrigger $trigger): string => $trigger->value,
                DownloadTrigger::cases(),
            ))],
            'series_id' => ['nullable', 'integer', 'exists:series,id'],
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Download::query()
            ->with($this->relations())
            ->latest('id');

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->whereHas('season.series', fn ($seriesQuery) => $seriesQuery
                ->where('title', 'like', '%'.$search.'%'));
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['trigger'])) {
            $query->where('trigger', $validated['trigger']);
        }

        if (isset($validated['season_id'])) {
            $query->where('season_id', $validated['season_id']);
        }

        if (isset($validated['series_id'])) {
            $query->whereHas('season', fn ($seasonQuery) => $seasonQuery
                ->where('series_id', $validated['series_id']));
        }

        return DownloadResource::collection(
            $query->paginate($validated['per_page'] ?? 20),
        );
    }

    public function show(Download $download): DownloadResource
    {
        return new DownloadResource($download->load($this->relations()));
    }

    public function cancel(Download $download, CancelDownloadAction $cancelDownload): DownloadResource|JsonResponse
    {
        try {
            $cancelled = $cancelDownload->execute($download);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return new DownloadResource($cancelled->load($this->relations()));
    }

    public function retry(Download $download, RetryDownloadAction $retryDownload): DownloadResource|JsonResponse
    {
        try {
            $retry = $retryDownload->execute($download);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return new DownloadResource($retry->load($this->relations()));
    }

    /** @return array<int, string> */
    private function relations(): array
    {
        return [
            'season:id,series_id,number',
            'season.series:id,title,poster_path,poster_url,year',
            'release',
            'items.episode',
        ];
    }
}
