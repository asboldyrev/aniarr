<?php

namespace App\Actions;

use App\Services\Logging\AniarrLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final class DownloadPosterAction
{
    public static function execute(string $url, int $seriesId): string|null
    {
        try {
            $response = Http::timeout(15)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $content = $response->body();
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $extension = strtolower($extension);
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $extension = 'jpg';
            }

            $filename = "posters/series_{$seriesId}.{$extension}";
            Storage::disk('public')->put($filename, $content);

            return $filename;
        } catch (\Throwable $e) {
            app(AniarrLogger::class)->exception($e);
            return null;
        }
    }
}
