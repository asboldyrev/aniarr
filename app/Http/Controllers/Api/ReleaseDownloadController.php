<?php

namespace App\Http\Controllers\Api;

use App\Actions\Downloads\ForceReleaseDownloadAction;
use App\Exceptions\ActiveDownloadExists;
use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadReleaseRequest;
use App\Http\Resources\DownloadResource;
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

        return response()->json(
            new DownloadResource($download->load(['release', 'items.episode'])),
            201,
        );
    }
}
