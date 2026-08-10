<?php

namespace App\Http\Controllers\Api;

use App\Enums\LogType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ActivityLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'series_id' => ['nullable', 'integer', 'exists:series,id'],
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],
            'download_id' => ['nullable', 'integer', 'exists:downloads,id'],
            'source' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'in:'.implode(',', array_map(fn (LogType $type): string => $type->value, LogType::all()))],
            'unresolved' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ActivityLog::query()
            ->with(['series:id,title', 'season:id,series_id,number'])
            ->latest('id');

        foreach (['series_id', 'season_id', 'download_id', 'source', 'event', 'type'] as $field) {
            if (array_key_exists($field, $validated)) {
                $query->where($field, $validated[$field]);
            }
        }

        if (($validated['unresolved'] ?? false) === true) {
            $query->whereIn('type', [LogType::WARNING->value, LogType::ERROR->value])
                ->whereNull('resolved_at');
        }

        return ActivityLogResource::collection(
            $query->paginate($validated['per_page'] ?? 30),
        );
    }

    public function resolve(ActivityLog $activityLog): ActivityLogResource
    {
        if ($activityLog->resolved_at === null) {
            $activityLog->update(['resolved_at' => now()]);
        }

        return new ActivityLogResource($activityLog->load(['series:id,title', 'season:id,series_id,number']));
    }

    public function reopen(ActivityLog $activityLog): ActivityLogResource
    {
        if ($activityLog->resolved_at !== null) {
            $activityLog->update(['resolved_at' => null]);
        }

        return new ActivityLogResource($activityLog->load(['series:id,title', 'season:id,series_id,number']));
    }
}
