<?php

namespace App\Services;

use App\Models\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TheTVDBService extends BaseApiService
{
    protected string $apiKey;
    protected ?string $pin = null;
    protected ?string $token = null;
    protected string $locale = 'eng';

    public function search(string $query, string $type = 'series'): array
    {
        $cacheKey = "thetvdb:search:" . md5(Str::slug($query) . ':' . $type . ':' . $this->locale);

        return Cache::remember($cacheKey, 3600, function () use ($query, $type) {
            try {
                $response = $this->get('search', [
                    'query' => $query,
                    'type' => $type,
                    'lang' => $this->locale,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                // TODO: Логировать ошибку
                return [];
            }
        });
    }

    /**
     * Получить постер сериала
     */
    public function getPoster(int $seriesId, string|null $lang = null): ?string
    {
        $lang = $lang ?? $this->locale;
        $images = $this->getImages($seriesId, 'poster', $lang);

        if (!empty($images)) {
            $firstImage = array_first($images['artworks']);

            if (isset($firstImage['image'])) {
                return $firstImage['image'];
            }

            if (isset($firstImage['imageUrl'])) {
                return $firstImage['imageUrl'];
            }

            if (isset($firstImage['url'])) {
                return $firstImage['url'];
            }

            if (isset($firstImage['thumbnail'])) {
                return $firstImage['thumbnail'];
            }

            if (isset($firstImage['imageId'])) {
                return "https://artworks.thetvdb.com/banners/{$firstImage['imageId']}";
            }

            if (isset($firstImage['remoteId'])) {
                return "https://artworks.thetvdb.com/banners/{$firstImage['remoteId']}";
            }

            if (isset($firstImage['id'])) {
                return "https://artworks.thetvdb.com/banners/{$firstImage['id']}";
            }
        }

        return null;
    }

    public function testConnection(): bool
    {
        return $this->login();
    }

    public function loadSettings(): void
    {
        $this->baseUrl = 'https://api4.thetvdb.com/v4';
        $this->apiKey = Settings::get('thetvdb_api_key', '');
        $this->pin = Settings::get('thetvdb_pin', null);

        $appLocale = config('app.locale', 'en');
        $this->locale = $this->convertLocaleToTheTVDB($appLocale);
    }

    public function getHeaders(): array
    {
        return [];
    }

    /**
     * Авторизация в TheTVDB API
     */
    public function login(): bool
    {
        if (!$this->isConfigured() || empty($this->apiKey)) {
            return false;
        }

        try {
            $data = [
                'apikey' => $this->apiKey,
            ];

            // Добавляем PIN если он указан (для user-supported ключей)
            if ($this->pin) {
                $data['pin'] = $this->pin;
            }

            $url = rtrim($this->baseUrl, '/') . '/login';
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $data);

            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['data']['token'] ?? null;
                return !empty($this->token);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Преобразовать локаль Laravel в формат TheTVDB
     */
    public function convertLocaleToTheTVDB(string $locale): string
    {
        $localeMap = [
            'en' => 'eng',
            'ru' => 'rus',
            'ja' => 'jpn',
            'fr' => 'fra',
            'es' => 'spa',
            'de' => 'deu',
            'it' => 'ita',
            'pt' => 'por',
            'zh' => 'zho',
            'ko' => 'kor',
        ];

        // Извлекаем базовый язык (например, 'ru' из 'ru_RU')
        $baseLocale = explode('_', $locale)[0];

        return $localeMap[$baseLocale] ?? 'eng';
    }

    /**
     * Получить изображения сериала
     */
    protected function getImages(int $seriesId, string|null $type = null, string|null $lang = null): array
    {
        $lang = $lang ?? $this->locale;
        $cacheKey = "thetvdb:images:" . md5($seriesId . ':' . ($type ?? 'all') . ':' . $lang);

        return Cache::remember($cacheKey, 3600, function () use ($seriesId, $type, $lang) {
            try {
                $query = ['lang' => $lang];
                if ($type) {
                    $query['type'] = $this->getTypes($type);
                }

                $response = $this->get("series/{$seriesId}/artworks", $query);

                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                // TODO: Логировать ошибку
                return [];
            }
        });
    }

    protected function getTypes(string $type): string
    {
        $types = [
            1 => 'banners',
            2 => 'posters',
            3 => 'backgrounds',
            5 => 'icons',
            6 => 'banners',
            7 => 'posters',
            8 => 'backgrounds',
            10 => 'icons',
            11 => 'screencap',
            12 => 'screencap',
            13 => 'photo',
            14 => 'posters',
            15 => 'backgrounds',
            16 => 'banners',
            18 => 'icons',
            19 => 'icons',
            20 => 'cinemagraphs',
            21 => 'cinemagraphs',
            22 => 'clearart',
            23 => 'clearlogo',
            24 => 'clearart',
            25 => 'clearlogo',
            26 => 'icons',
            27 => 'posters',
        ];

        return implode(',', array_keys($types, $type));
    }
}
