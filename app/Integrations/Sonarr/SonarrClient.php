<?php

namespace App\Integrations\Sonarr;

use App\Integrations\BaseApiClient;
use App\Integrations\Sonarr\Dto\importFile;
use App\Integrations\Sonarr\Dto\RootFolder;
use App\Integrations\Sonarr\Dto\SonarrEpisode;
use App\Integrations\Sonarr\Dto\SonarrSeries;
use App\Models\Settings;
use App\Services\Logging\AniarrLogger;

/**
 * Клиент API Sonarr для управления сериалами и эпизодами.
 */
class SonarrClient extends BaseApiClient
{
    protected string $apiKey;

    /**
     * Проверяет, существует ли сериал в Sonarr по TVDB ID.
     *
     * @param  int  $tvdbId  Идентификатор сериала в TVDB
     * @return bool true, если сериал существует
     */
    public function hasSeries(int $tvdbId): bool
    {
        $response = $this->get('series', ['tvdbId' => $tvdbId]);
        $data = $response->successful() ? $response->json() : null;

        return is_array($data) && ! empty($data);
    }

    /**
     * Найти аниме по tvdb_id.
     *
     * @param  int  $tvdbId  Идентификатор сериала в TVDB
     * @return SonarrSeries|null Данные сериала, если найден
     */
    public function findByTvdbId(int $tvdbId): ?SonarrSeries
    {
        $response = $this->get('series/lookup', ['term' => 'tvdb:' . $tvdbId]);
        $data = $response->successful() ? $response->json() : null;

        if (is_array($data) && ! empty($data)) {
            return SonarrSeries::makeFromResponse($data[0]);
        }

        return null;
    }

    /**
     * Добавить сериал в Sonarr по данным lookup (rootFolderPath и qualityProfileId обязательны).
     *
     * @param  SonarrSeries  $lookupSeries  Данные сериала из поиска
     * @param  string  $rootFolderPath  Путь к корневой папке
     * @param  int  $qualityProfileId  ID профиля качества
     * @return SonarrSeries|null Данные созданного сериала
     */
    public function addSeriesFromLookup(SonarrSeries $lookupSeries, string $rootFolderPath, int $qualityProfileId): ?SonarrSeries
    {
        $payload = $lookupSeries->toArray();
        $payload['rootFolderPath'] = $rootFolderPath;
        $payload['qualityProfileId'] = $qualityProfileId;
        $payload['monitored'] = $payload['monitored'] ?? true;
        $payload['addOptions'] = $payload['addOptions'] ?? ['searchForMissingEpisodes' => false];

        $response = $this->post('series', $payload);
        $data = $response->json();

        if ($response->successful() && !empty($data)) {
            return SonarrSeries::makeFromResponse($data);
        }

        return null;
    }

    /**
     * Команда ManualImport (POST /api/v3/command). Тело: name, importMode, files.
     *
     * @param  array<importFile>  $files  Файлы для импорта
     * @param  string  $importMode  Режим импорта (move, copy)
     * @return array|null Ответ команды
     */
    public function sendManualImportCommand(array $files, string $importMode = 'move'): ?array
    {
        if ($files === []) {
            return null;
        }
        $body = [
            'name' => 'ManualImport',
            'importMode' => $importMode,
            'files' => array_map(fn(importFile $item) => $item->toArray(), $files),
        ];

        $response = $this->post('command', $body);
        if (! $response->successful()) {
            app(AniarrLogger::class)->warning('[Sonarr] ошибка импорта', [
                'status' => $response->status(),
                'body' => $response->body(),
                'files_count' => count($files),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Получить статус команды Sonarr (для опроса до completed/failed).
     *
     * @param  int  $commandId  ID команды Sonarr
     * @return array|null Данные статуса команды
     */
    public function getCommand(int $commandId): ?array
    {
        $response = $this->get("command/{$commandId}");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Получить список серий для аниме.
     *
     * @param  int  $seriesId  ID сериала в Sonarr
     * @return array<SonarrEpisode> Список эпизодов
     */
    public function getEpisodes(int $seriesId): array
    {
        $response = $this->get('episode', ['seriesId' => $seriesId, 'includeEpisodeFile' => 'true']);
        $episodes = $response->successful() ? $response->json() : null;

        if (is_array($episodes)) {
            return array_map(fn($episode) => SonarrEpisode::makeFromResponse($episode), $episodes);
        }

        return [];
    }

    /**
     * Найти серию по sonarr_id.
     *
     * @param  int  $sonarrId  ID эпизода в Sonarr
     * @return array|null Данные эпизода
     */
    public function findEpisodeBySonarrId(int $sonarrId): ?array
    {
        $response = $this->get('episode/' . $sonarrId);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Проверить подключение к Sonarr.
     *
     * @return bool true, если подключение успешно
     */
    public function testConnection(): bool
    {
        if (! $this->isConfigured()) {
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
     * Получить профили качества Sonarr.
     *
     * @return array Профили качества
     */
    public function getQualityProfiles(): array
    {
        $response = $this->get('qualityProfile');
        $data = $response->successful() ? $response->json() : null;

        return is_array($data) ? $data : [];
    }

    /**
     * Получить корневые папки Sonarr.
     *
     * @return array<RootFolder> Корневые папки
     */
    public function getRootFolders(): array
    {
        $response = $this->get('rootFolder');
        $data = $response->successful() ? $response->json() : null;

        if (is_array($data)) {
            return array_map(fn($folder) => RootFolder::makeFromResponse($folder), $data);
        }

        return [];
    }

    /**
     * Загрузить настройки Sonarr из базы данных.
     */
    protected function loadSettings(): void
    {
        $this->baseUrl = Settings::get('sonarr_url', '') . '/api/v3';
        $this->apiKey = Settings::get('sonarr_api_key', '');
    }

    /**
     * Получить заголовки для запросов.
     *
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        return [
            'X-Api-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}
