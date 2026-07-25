<?php

namespace App\Integrations;

use App\Models\Settings;

class SonarrClient extends BaseApiClient
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

    public function hasSeries(int $tvdbId): int|null
    {
        $response = $this->get('series', ['tvdbId' => $tvdbId]);
        $data = $response->successful() ? $response->json() : null;

        return is_array($data) && !empty($data);
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
     * Добавить сериал в Sonarr по данным lookup (rootFolderPath и qualityProfileId обязательны)
     */
    public function addSeriesFromLookup(array $lookupSeries, string $rootFolderPath, int $qualityProfileId): ?array
    {
        $payload = $lookupSeries;
        $payload['rootFolderPath'] = $rootFolderPath;
        $payload['qualityProfileId'] = $qualityProfileId;
        $payload['monitored'] = $payload['monitored'] ?? true;
        $payload['addOptions'] = $payload['addOptions'] ?? ['searchForMissingEpisodes' => false];
        return $this->addSeries($payload);
    }

    /**
     * Получить список серий для аниме
     */
    public function getEpisodes(int $seriesId): array
    {
        $response = $this->get('episode', ['seriesId' => $seriesId, 'includeEpisodeFile' => 'true']);
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
     * Получить профили качества Sonarr
     */
    public function getQualityProfiles(): array
    {
        $response = $this->get('qualityProfile');
        $data = $response->successful() ? $response->json() : null;
        return is_array($data) ? $data : [];
    }

    /**
     * Получить корневые папки Sonarr
     */
    public function getRootFolders(): array
    {
        $response = $this->get('rootFolder');
        $data = $response->successful() ? $response->json() : null;
        return is_array($data) ? $data : [];
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
