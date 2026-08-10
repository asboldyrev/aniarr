<?php

namespace App\Http\Controllers\Api;

use App\Actions\Downloads\ForceReleaseDownloadAction;
use App\Exceptions\ActiveDownloadExists;
use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadReleaseRequest;
use App\Models\Release;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class ReleaseDownloadController extends Controller
{
    public function store(
        DownloadReleaseRequest $request,
        Release $release,
        ForceReleaseDownloadAction $forceDownload,
    ): JsonResponse {
        try {
            $download = $forceDownload->execute(
                $release,
                $request->episodeIds(),
            );
        } catch (ActiveDownloadExists $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'id' => $download->id,
            'seasonId' => $download->season_id,
            'releaseId' => $download->release_id,
            'trigger' => $download->trigger->value,
            'status' => $download->status->value,
            'items' => $download->items->map(fn ($item): array => [
                'id' => $item->id,
                'episodeId' => $item->episode_id,
                'reason' => $item->reason->value,
            ])->values(),
        ], 201);
    }
}
