<?php

namespace App\Services;

use App\Exceptions\FailedRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseApiService
{
    protected string $baseUrl;

    protected array $credentials = [];

    /**
     * Инициализация сервиса с настройками из базы данных
     */
    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * Проверить подключение к сервису
     */
    abstract public function testConnection(): bool;

    /**
     * Выполнить GET запрос
     *
     * @param  int|null  $timeout  Таймаут в секундах (по умолчанию 10)
     */
    protected function get(string $endpoint, array $query = [], ?int $timeout = null): Response
    {
        $options = ['query' => $query];
        if ($timeout !== null) {
            $options['timeout'] = $timeout;
        }

        return $this->request('get', $endpoint, $options);
    }

    /**
     * Выполнить POST запрос
     */
    protected function post(string $endpoint, array $data = []): Response
    {
        return $this->request('post', $endpoint, ['json' => $data]);
    }

    /**
     * Выполнить PUT запрос
     */
    protected function put(string $endpoint, array $data = []): Response
    {
        return $this->request('put', $endpoint, ['json' => $data]);
    }

    /**
     * Выполнить DELETE запрос
     */
    protected function delete(string $endpoint, array $query = []): Response
    {
        return $this->request('delete', $endpoint, ['query' => $query]);
    }

    /**
     * Базовый метод для выполнения HTTP запросов
     */
    protected function request(string $method, string $endpoint, array $options = []): Response
    {
        if (empty($this->baseUrl)) {
            throw new \RuntimeException('Base URL не настроен');
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $timeout = $options['timeout'] ?? 10;
        unset($options['timeout']);
        $http = Http::timeout($timeout)->withHeaders($this->getHeaders());

        if (isset($options['query'])) {
            $http = $http->withQueryParameters($options['query']);
        }

        /** @var Response $response */
        $response = $http->{strtolower($method)}($url, $options['json'] ?? []);

        if (! $response->successful()) {
            $message = sprintf(
                'Запрос: %s не выполнен. Ошибка: %s',
                $url,
                $response->body(),
            );

            throw new FailedRequest($message);
        }

        return $response;
    }

    /**
     * Получить заголовки для запросов
     */
    abstract protected function getHeaders(): array;

    /**
     * Загрузить настройки из базы данных
     */
    abstract protected function loadSettings(): void;

    /**
     * Проверить, что сервис настроен
     */
    protected function isConfigured(): bool
    {
        return ! empty($this->baseUrl);
    }
}
