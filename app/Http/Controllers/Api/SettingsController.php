<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Integrations\JellyfinClient;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Integrations\Sonarr\SonarrClient;
use App\Integrations\Tvdb\TvdbClient;
use App\Models\Settings;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $stored = Settings::getAll();

        $settings = [];
        foreach (UpdateSettingsRequest::KEYS as $key) {
            $settings[$key] = $stored->get($key);
        }

        return response()->json(['data' => $settings]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        foreach (UpdateSettingsRequest::KEYS as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            Settings::set($key, $validated[$key]);
        }

        return $this->index();
    }

    public function test(string $service): JsonResponse
    {
        $connected = match ($service) {
            'sonarr' => (new SonarrClient)->testConnection(),
            'qbittorrent' => (new QBittorrentClient)->testConnection(),
            'jellyfin' => (new JellyfinClient)->testConnection(),
            'thetvdb' => (new TvdbClient)->testConnection(),
            default => throw new InvalidArgumentException('Неизвестная интеграция.'),
        };

        return response()->json([
            'data' => [
                'service' => $service,
                'connected' => $connected,
                'message' => $connected
                    ? 'Подключение успешно.'
                    : 'Не удалось подключиться. Проверьте сохранённые настройки.',
            ],
        ]);
    }
}
