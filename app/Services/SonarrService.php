<?php

namespace App\Services;

use App\Models\Settings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class SonarrService extends BaseApiService
{
    protected string $apiKey;

    /**
     * Получить список всех сериалов
     */
    public function getSeries(): array
    {
        $response = $this->get('series');
        $data = $response->successful() ? $response->json() : null;
        return is_array($data) ? $data : [];
    }

    /**
     * Найти аниме по tvdb_id
     */
    public function findByTvdbId(int $tvdbId): ?array
    {
        $response = $this->get('series/lookup', ['term' => 'tvdb:' . $tvdbId]);
        $data = $response->successful() ? $response->json() : null;

        if (is_array($data) && !empty($data)) {
            return $data[0];
        }

        return null;
    }

    /**
     * Добавить аниме
     */
    public function addSeries(array $data): ?array
    {
        $response = $this->post('series', $data);
        return $response->successful() ? $response->json() : null;
    }

    /**
     * Получить список серий для аниме
     */
    public function getEpisodes(int $seriesId): array
    {
        $response = $this->get('episode', ['seriesId' => $seriesId]);
        $data = $response->successful() ? $response->json() : null;
        return is_array($data) ? $data : [];
    }

    /**
     * Найти серию по sonarr_id
     */
    public function findEpisodeBySonarrId(int $sonarrId): ?array
    {
        $response = $this->get('episode/' . $sonarrId);
        return $response->successful() ? $response->json() : null;
    }

    /**
     * Проверить подключение к Sonarr
     */
    public function testConnection(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->get('system/status');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Загрузить настройки Sonarr из базы данных
     */
    protected function loadSettings(): void
    {
        $this->baseUrl = Settings::get('sonarr_url', '') . '/api/v3';
        $this->apiKey = Settings::get('sonarr_api_key', '');
    }

    /**
     * Получить заголовки для запросов
     */
    protected function getHeaders(): array
    {
        return [
            'X-Api-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}
