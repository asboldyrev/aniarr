<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Integrations\Tvdb\TvdbClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TvdbSeriesSearchController extends Controller
{
    public function __invoke(Request $request, TvdbClient $tvdbClient): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:150'],
        ]);

        $results = collect($tvdbClient->search($validated['query']))
            ->map(fn (array $item): array => $this->normalize($item))
            ->filter(fn (array $item): bool => $item['thetvdbId'] > 0 && $item['title'] !== '')
            ->values()
            ->take(10)
            ->all();

        return response()->json(['data' => $results]);
    }

    /** @param array<string, mixed> $item */
    private function normalize(array $item): array
    {
        $aliases = array_values(array_filter(
            is_array($item['aliases'] ?? null) ? $item['aliases'] : [],
            fn ($alias): bool => is_string($alias) && trim($alias) !== '',
        ));

        $title = trim((string) ($item['name'] ?? $item['title'] ?? ''));
        $originalTitle = collect($aliases)
            ->first(fn (string $alias): bool => mb_strtolower($alias) !== mb_strtolower($title));

        return [
            'thetvdbId' => (int) ($item['tvdb_id'] ?? $item['id'] ?? 0),
            'title' => $title,
            'originalTitle' => $originalTitle,
            'year' => isset($item['year']) && is_numeric($item['year']) ? (int) $item['year'] : null,
            'posterUrl' => $item['image_url'] ?? $item['image'] ?? $item['thumbnail'] ?? null,
            'overview' => $item['overview'] ?? null,
        ];
    }
}
