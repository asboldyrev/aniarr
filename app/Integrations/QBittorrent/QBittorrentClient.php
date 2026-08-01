<?php

namespace App\Integrations\QBittorrent;

use App\Exceptions\BaseUrlNotConfigured;
use App\Integrations\BaseApiClient;
use App\Models\Settings;
use App\Support\Formatting\TransferFormatter;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Клиент API QBittorrent для управления торрентами.
 */
class QBittorrentClient extends BaseApiClient
{
    protected ?string $cookie = null;

    protected array $credentials = [];

    /**
     * Авторизация в qBittorrent.
     *
     * @return bool true, если аутентификация прошла успешно
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
                    $this->cookie = 'SID=' . $matches[1];

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Проверить подключение к сервису.
     *
     * @return bool true, если подключение успешно
     */
    public function testConnection(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        return $this->login();
    }

    /**
     * Добавление торрента через URL.
     *
     * @param  string  $url  URL торрента или магнет-ссылка
     * @param  array  $options  Дополнительные опции qBittorrent (savepath, category и т.д.)
     * @return bool true, если торрент добавлен успешно
     */
    public function addTorrentUrl(string $url, array $options = []): bool
    {
        if (! $this->cookie && ! $this->login()) {
            return false;
        }

        $params = array_merge(['urls' => $url], $options);

        $response = Http::withHeaders($this->getHeaders())
            ->asForm()
            ->post(rtrim($this->baseUrl, '/') . '/api/v2/torrents/add', $params);

        return $response->successful();
    }

    /**
     * Старт загрузки торрента.
     *
     * @param  string  $hash  Хэш торрента
     * @return bool true, если команда запуска выполнена успешно
     */
    public function startTorrent(string $hash): bool
    {
        if (! $this->cookie && ! $this->login()) {
            return false;
        }

        // В qBittorrent >= 5.0 эндпоинт /resume заменён на /start
        // https://github.com/qbittorrent/qBittorrent/issues/22766
        $response = $this->post('/api/v2/torrents/start', ['hashes' => $hash]);

        return $response->successful();
    }

    /**
     * Стоп загрузки торрента.
     *
     * @param  string  $hash  Хэш торрента
     * @return bool true, если команда остановки выполнена успешно
     */
    public function stopTorrent(string $hash): bool
    {
        if (! $this->cookie && ! $this->login()) {
            return false;
        }

        // В qBittorrent >= 5.0 эндпоинт /pause заменён на /stop
        // https://github.com/qbittorrent/qBittorrent/issues/22766
        $response = $this->post('/api/v2/torrents/stop', ['hashes' => $hash]);

        return $response->successful();
    }

    /**
     * Выставление приоритетов файлов.
     *
     * @param  string  $hash  Хэш торрента
     * @param  string  $index  индекс файлов (через |)
     * @param  int  $priority  Приоритет (0 = do not download, 1 = normal, 6 = high, 7 = maximal)
     * @return bool True if priority set successfully
     */
    public function setFilePriority(string $hash, string $index, int $priority): bool
    {
        if (! $this->cookie && ! $this->login()) {
            return false;
        }

        $response = $this->post('/api/v2/torrents/filePrio', [
            'hash' => $hash,
            'id' => $index,
            'priority' => $priority,
        ]);

        return $response->successful();
    }

    /**
     * Устанавливает приоритеты сразу нескольким файлам.
     *
     * @param  string  $hash  Хэш торрента
     * @param  array  $priorities  Массив [индекс_файла => приоритет]
     * @return bool true, если все приоритеты установлены успешно
     */
    public function setFilePriorities(string $hash, array $priorities): bool
    {
        if (! $this->cookie && ! $this->login()) {
            return false;
        }

        foreach ($priorities as $index => $priority) {
            $id = (string) $index;
            $this->setFilePriority($hash, $id, $priority);
        }

        return true;
    }

    /**
     * Удаление торрента вместе с файлами.
     *
     * @param  string  $hash  Хэш торрента
     * @return bool true, если удаление выполнено успешно
     */
    public function deleteTorrent(string $hash): bool
    {
        if (! $this->cookie && ! $this->login()) {
            return false;
        }

        $response = $this->post('/api/v2/torrents/delete', [
            'hashes' => $hash,
            'deleteFiles' => 'true',
        ]);

        return $response->successful();
    }

    /**
     * Получает статистику по прогрессу торрента.
     *
     * @param  string  $hash  Хэш торрента
     * @return array|null Массив с прогрессом, скоростью, ETA, состоянием и файлами; null, если торрент не найден
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

        if (! $torrent) {
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
     * Получает список файлов торрента.
     *
     * @param  string  $hash  Хэш торрента
     * @return array Список файлов с индексом, именем, размером, прогрессом, приоритетом
     */
    public function getTorrentFiles(string $hash): array
    {
        $response = $this->get('/api/v2/torrents/files', [
            'hash' => $hash,
        ])->json();

        if (! is_array($response)) {
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
     * Получить список всех торрентов с тегом.
     *
     * @param  string  $tag  Тег для фильтрации
     * @return array Список торрентов
     */
    public function getTorrentsByTag(string $tag): array
    {
        $response = $this->get('/api/v2/torrents/info', ['tag' => $tag]);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Удалить тег у всех торрентов.
     *
     * @param  string  $tag  Тег для удаления
     * @return bool true, если удаление выполнено успешно
     */
    public function deleteTags(string $tag): bool
    {
        $response = $this->post('/api/v2/torrents/deleteTags', [
            'form' => ['tags' => $tag],
        ]);

        return $response->successful();
    }

    /**
     * Загрузить настройки из базы данных.
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
     * Получить заголовки для запросов.
     *
     * @return array<string, string>
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
     * Переопределяем request для поддержки авторизации через куки.
     *
     * @param  string  $method  HTTP-метод (get, post и т.д.)
     * @param  string  $endpoint  Конечная точка API
     * @param  array  $options  Опции запроса (timeout, query, json)
     *
     * @throws BaseUrlNotConfigured
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
