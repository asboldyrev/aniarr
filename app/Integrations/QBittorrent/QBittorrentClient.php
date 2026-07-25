<?php

namespace App\Integrations\QBittorrent;

use App\Exceptions\BaseUrlNotConfigured;
use App\Integrations\BaseApiClient;
use App\Models\Settings;
use App\Support\Formatting\TransferFormatter;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class QBittorrentClient extends BaseApiClient
{
    protected ?string $cookie = null;

    protected array $credentials = [];

    /**
     * Авторизация в qBittorrent
     */
    public function login(): bool
    {
        $response = Http::asForm()->post(rtrim($this->baseUrl, '/') . '/api/v2/auth/login', [
            'username' => $this->credentials['username'],
            'password' => $this->credentials['password'],
        ]);

        if ($response->successful()) {
            $cookies = $response->header('Set-Cookie');
            if ($cookies) {
                // Извлекаем SID из кук
                if (preg_match('/SID=([^;]+)/', $cookies, $matches)) {
                    $this->cookie = "SID=" . $matches[1];
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Проверить подключение к сервису
     */
    public function testConnection(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        return $this->login();
    }

    /**
     * Добавление торрента через URL
     */
    public function addTorrentUrl(string $url, array $options = []): bool
    {
        if (!$this->cookie && !$this->login()) {
            return false;
        }

        $params = array_merge(['urls' => $url], $options);

        $response = Http::withHeaders($this->getHeaders())
            ->asForm()
            ->post(rtrim($this->baseUrl, '/') . '/api/v2/torrents/add', $params);

        return $response->successful();
    }

    /**
     * Старт загрузки торрента
     */
    public function startTorrent(string $hash): bool
    {
        if (!$this->cookie && !$this->login()) {
            return false;
        }

        $response = $this->post('api/v2/torrents/resume', ['hashes' => $hash]);
        return $response->successful();
    }

    /**
     * Стоп загрузки торрента
     */
    public function stopTorrent(string $hash): bool
    {
        if (!$this->cookie && !$this->login()) {
            return false;
        }

        $response = $this->post('api/v2/torrents/pause', ['hashes' => $hash]);
        return $response->successful();
    }

    /**
     * Выставление приоритетов файлов
     *
     * @param string $hash Хэш торрента
     * @param string $index индекс файлов (через |)
     * @param int $priority Приоритет (0 = do not download, 1 = normal, 6 = high, 7 = maximal)
     */
    public function setFilePriority(string $hash, string $index, int $priority): bool
    {
        if (!$this->cookie && !$this->login()) {
            return false;
        }

        $response = $this->get('api/v2/torrents/filePrio', [
            'hashes' => $hash,
            'index' => $index,
            'priority' => $priority
        ]);

        return $response->successful();
    }

    /**
     * Устанавливает приоритеты сразу нескольким файлам
     *
     * @param string $hash Torrent hash
     * @param array $priorities Array of [fileIndex => priority]
     * @return bool
     */
    public function setFilePriorities(string $hash, array $priorities): bool
    {
        if (!$this->cookie && !$this->login()) {
            return false;
        }

        foreach ($priorities as $index => $priority) {
            $id = (string) $index;
            $this->setFilePriority($hash, $id, $priority);
        }

        return true;
    }

    /**
     * Удаление торрента вместе с файлами
     */
    public function deleteTorrent(string $hash): bool
    {
        if (!$this->cookie && !$this->login()) {
            return false;
        }

        $response = $this->get('api/v2/torrents/delete', [
            'hashes' => $hash,
            'deleteFiles' => 'true'
        ]);

        return $response->successful();
    }

    /**
     * Получает статистику по прогрессу торрента
     */
    public function getTorrentProgress(string $hash): ?array
    {
        $response = $this->get('/api/v2/torrents/info', [
            'hashes' => $hash,
        ]);

        if (empty($response)) {
            return null;
        }

        $torrent = $response[0] ?? null;

        if (!$torrent) {
            return null;
        }

        $speed = TransferFormatter::speed($torrent['dlspeed'] ?? 0);
        $eta = TransferFormatter::eta($torrent['eta'] ?? -1);

        return [
            'progress' => ($torrent['progress'] ?? 0) * 100,
            'speed' => $speed,
            'eta' => $eta,
            'state' => $torrent['state'] ?? 'unknown',
            'files' => [],
        ];
    }

    /**
     * Получает список файлов торрента
     */
    public function getTorrentFiles(string $hash): array
    {
        $response = $this->get('/api/v2/torrents/files', [
            'hash' => $hash,
        ]);

        if (!is_array($response)) {
            return [];
        }

        return array_map(function ($file) {
            return [
                'index' => $file['index'] ?? 0,
                'name' => $file['name'] ?? '',
                'size' => $file['size'] ?? 0,
                'progress' => $file['progress'] ?? 0,
                'priority' => $file['priority'] ?? 0,
            ];
        }, $response);
    }

    /**
     * Загрузить настройки из базы данных
     */
    protected function loadSettings(): void
    {
        $this->baseUrl = Settings::get('qbittorrent_url', '');
        $this->credentials = [
            'username' => Settings::get('qbittorrent_username', ''),
            'password' => Settings::get('qbittorrent_password', ''),
        ];
    }

    /**
     * Получить заголовки для запросов
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($this->cookie) {
            $headers['Cookie'] = $this->cookie;
        }

        return $headers;
    }

    /**
     * Переопределяем request для поддержки авторизации через куки
     */
    protected function request(string $method, string $endpoint, array $options = []): Response
    {
        if (empty($this->baseUrl)) {
            throw new BaseUrlNotConfigured('Base URL для QBittorrent не настроен');
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $timeout = $options['timeout'] ?? 10;
        unset($options['timeout']);

        $http = Http::timeout($timeout)->withHeaders($this->getHeaders());

        if (isset($options['query'])) {
            $http = $http->withQueryParameters($options['query']);
        }

        // qBittorrent API часто ожидает x-www-form-urlencoded для POST запросов
        if ($method === 'post' && isset($options['json'])) {
            $response = $http->asForm()->post($url, $options['json']);
        } else {
            $response = $http->{strtolower($method)}($url, $options['json'] ?? []);
        }

        return $response;
    }
}
