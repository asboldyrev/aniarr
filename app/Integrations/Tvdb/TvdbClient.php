<?php

namespace App\Integrations\Tvdb;

use App\Integrations\BaseApiClient;
use App\Models\Settings;
use App\Services\Logging\AniarrLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TvdbClient extends BaseApiClient
{
    protected string $apiKey;

    protected ?string $pin = null;

    protected ?string $token = null;

    protected string $locale = 'eng';

    /**
     * Получить данные по сериалу
     */
    public function getSeries(int|string $id): array
    {
        $cacheKey = 'thetvdb:getSeries:'.md5($id.':'.$this->locale);

        return Cache::remember($cacheKey, 3600, function () use ($id) {
            try {
                $response = $this->get('series/'.$id);
                $translation = $this->get(sprintf('series/%s/translations/%s', $id, $this->locale));

                if ($response->successful() && $translation->successful()) {
                    $data = $response->json('data');

                    if (empty($data)) {
                        return [];
                    }

                    $data['translation'] = $translation->json('data');

                    return $data;
                }

                return [];
            } catch (\Exception $e) {
                app(AniarrLogger::class)->exception($e);

                return [];
            }
        });
    }

    /**
     * Поиск сериала
     */
    public function search(string $query, string $type = 'series'): array
    {
        $cacheKey = 'thetvdb:search:'.md5(Str::slug($query).':'.$type.':'.$this->locale);

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
                app(AniarrLogger::class)->exception($e);

                return [];
            }
        });
    }

    /**
     * Получить постер сериала
     */
    public function getPoster(int $seriesId, ?string $lang = null): ?string
    {
        $lang = $lang ?: $this->locale;
        $images = $this->getImages($seriesId, 'poster', $lang);

        if (! empty($images)) {
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

            if (! empty($images['image'])) {
                return $images['image'];
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
        $this->locale = app(TvdbLocaleMapper::class)->map($appLocale);

        $this->login();
    }

    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
        ];
    }

    /**
     * Авторизация в TheTVDB API
     */
    public function login(): bool
    {
        if (! $this->isConfigured() || empty($this->apiKey)) {
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

            $url = rtrim($this->baseUrl, '/').'/login';
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $data);

            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['data']['token'] ?? null;

                return ! empty($this->token);
            }

            return false;
        } catch (\Exception $e) {
            app(AniarrLogger::class)->exception($e);

            return false;
        }
    }

    /**
     * Получить изображения сериала
     */
    protected function getImages(int $seriesId, ?string $type = null, ?string $lang = null): array
    {
        $lang = $lang ?? $this->locale;
        $cacheKey = 'thetvdb:images:'.md5($seriesId.':'.($type ?? 'all').':'.$lang);

        return Cache::remember($cacheKey, 3600, function () use ($seriesId, $type, $lang) {
            try {
                $query = ['lang' => $lang];
                if ($type) {
                    $query['type'] = app(TvdbImageTypeMapper::class)->map($type);
                }

                $response = $this->get("series/{$seriesId}/artworks", $query);

                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                app(AniarrLogger::class)->exception($e);

                return [];
            }
        });
    }
}
