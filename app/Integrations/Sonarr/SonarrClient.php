<?php

namespace App\Integrations\Sonarr;

use App\Integrations\BaseApiClient;
use App\Integrations\Sonarr\Dto\RootFolder;
use App\Integrations\Sonarr\Dto\SonarrEpisode;
use App\Integrations\Sonarr\Dto\SonarrSeries;
use App\Models\Settings;
use App\Services\Logging\AniarrLogger;
use RuntimeException;

/**
 * Клиент API Sonarr для управления сериалами и эпизодами.
 */
class SonarrClient extends BaseApiClient
{
    protected string $apiKey;

    public function hasSeries(int $tvdbId): bool
    {
        return $this->getSeriesByTvdbId($tvdbId) !== null;
    }

    public function getSeriesByTvdbId(int $tvdbId): ?SonarrSeries
    {
        $response = $this->get('series', ['tvdbId' => $tvdbId]);
        $data = $response->successful() ? $response->json() : null;

        if (! is_array($data) || empty($data)) {
            return null;
        }

        $series = array_is_list($data) ? ($data[0] ?? null) : $data;

        return is_array($series) ? SonarrSeries::makeFromResponse($series) : null;
    }

    public function findByTvdbId(int $tvdbId): ?SonarrSeries
    {
        $response = $this->get('series/lookup', ['term' => 'tvdb:'.$tvdbId]);
        $data = $response->successful() ? $response->json() : null;

        if (is_array($data) && ! empty($data)) {
            return SonarrSeries::makeFromResponse($data[0]);
        }

        return null;
    }

    public function addSeriesFromLookup(SonarrSeries $lookupSeries, string $rootFolderPath, int $qualityProfileId): ?SonarrSeries
    {
        $payload = $lookupSeries->toArray();
        $payload['rootFolderPath'] = $rootFolderPath;
        $payload['qualityProfileId'] = $qualityProfileId;
        $payload['monitored'] = $payload['monitored'] ?? true;
        $payload['addOptions'] = $payload['addOptions'] ?? ['searchForMissingEpisodes' => false];

        $response = $this->post('series', $payload);
        $data = $response->json();

        if ($response->successful() && ! empty($data)) {
            return SonarrSeries::makeFromResponse($data);
        }

        return null;
    }

    /**
     * Просит Sonarr самостоятельно разобрать файлы для ManualImport.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getManualImportCandidates(string $folder, int $seriesId): array
    {
        $response = $this->get('manualimport', [
            'folder' => $folder,
            'seriesId' => $seriesId,
            'filterExistingFiles' => 'false',
        ]);

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $files
     */
    public function sendManualImportCommand(array $files, string $importMode = 'move'): ?array
    {
        if ($files === []) {
            return null;
        }

        $response = $this->post('command', [
            'name' => 'ManualImport',
            'importMode' => $importMode,
            'files' => $files,
        ]);

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

    public function getCommand(int $commandId): ?array
    {
        $response = $this->get("command/{$commandId}");

        return $response->successful() ? $response->json() : null;
    }

    /** @return array<SonarrEpisode> */
    public function getEpisodes(int $seriesId): array
    {
        $response = $this->get('episode', ['seriesId' => $seriesId, 'includeEpisodeFile' => 'true']);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Sonarr вернул ошибку при получении эпизодов сериала %d: HTTP %d',
                $seriesId,
                $response->status(),
            ));
        }

        $episodes = $response->json();
        if (! is_array($episodes)) {
            throw new RuntimeException('Sonarr вернул некорректный ответ со списком эпизодов');
        }

        return array_map(fn ($episode) => SonarrEpisode::makeFromResponse($episode), $episodes);
    }

    public function findEpisodeBySonarrId(int $sonarrId): ?array
    {
        $response = $this->get('episode/'.$sonarrId);

        return $response->successful() ? $response->json() : null;
    }

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

    public function getQualityProfiles(): array
    {
        $response = $this->get('qualityProfile');
        $data = $response->successful() ? $response->json() : null;

        return is_array($data) ? $data : [];
    }

    /** @return array<RootFolder> */
    public function getRootFolders(): array
    {
        $response = $this->get('rootFolder');
        $data = $response->successful() ? $response->json() : null;

        if (is_array($data)) {
            return array_map(fn ($folder) => RootFolder::makeFromResponse($folder), $data);
        }

        return [];
    }

    protected function loadSettings(): void
    {
        $this->baseUrl = Settings::get('sonarr_url', '').'/api/v3';
        $this->apiKey = Settings::get('sonarr_api_key', '');
    }

    protected function getHeaders(): array
    {
        return [
            'X-Api-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}
