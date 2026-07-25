<?php

namespace App\Integrations;

use App\Models\Settings;
use App\Services\Logging\AniarrLogger;
use Exception;
use Illuminate\Http\Client\Response;

class JellyfinService extends BaseApiClient
{
    protected string $apiKey;

    /**
     * Загрузить настройки Jellyfin из базы данных
     */
    protected function loadSettings(): void
    {
        $this->baseUrl = Settings::get('jellyfin_url', '');
        $this->apiKey = Settings::get('jellyfin_api_key', '');
    }

    /**
     * Получить заголовки для запросов
     */
    protected function getHeaders(): array
    {
        return [
            'X-Emby-Authorization' => 'MediaBrowser Client="AniArr", Device="Server", DeviceId="AniArr", Version="1.0.0", Token="' . $this->apiKey . '"',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Проверить подключение к Jellyfin
     */
    public function testConnection(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->getSystemInfo();
            return $response->successful();
        } catch (Exception $e) {
            app(AniarrLogger::class)->exception($e);
            return false;
        }
    }

    /**
     * Обновить библиотеку
     */
    public function refreshLibrary(?string $libraryId = null): bool
    {
        if ($libraryId) {
            $response = $this->post("Library/Series/Updated", ['Id' => $libraryId]);
        } else {
            $response = $this->post("Library/Refresh");
        }

        return $response->successful();
    }

    /**
     * Получить информацию о системе
     */
    public function getSystemInfo(): Response
    {
        return $this->get('System/Info');
    }

    /**
     * Получить список библиотек
     */
    public function getLibraries(): array
    {
        $response = $this->get('Library/VirtualFolders');
        return $response->successful() ? $response->json() : [];
    }
}
