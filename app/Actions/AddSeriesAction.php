<?php

namespace App\Actions;

use App\Enums\Status;
use App\Integrations\Sonarr\SonarrClient;
use App\Integrations\Tvdb\TvdbClient;
use App\Integrations\Tvdb\TvdbLocaleMapper;
use App\Jobs\AddSeriesToSonarrJob;
use App\Jobs\SyncSeriesWithSonarrJob;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;

/**
 * Действие для добавления нового сериала в систему.
 *
 * Получает данные сериала из TVDB, загружает постер и запускает интеграцию с Sonarr.
 */
final class AddSeriesAction
{
    /**
     * Выполняет процесс добавления сериала.
     *
     * @param  int|string  $tvdbId  Идентификатор сериала в TVDB
     * @param  array  $rssFeeds  Массив RSS-лент для мониторинга торрентов
     * @param  Series|null  $series  Существующая модель сериала (опционально)
     */
    public function execute(int|string $tvdbId, array $rssFeeds, ?Series $series = null): void
    {
        $tvdbClient = new TvdbClient;
        $tvdbData = $tvdbClient->getSeries($tvdbId);
        $posterUrl = $tvdbClient->getPoster($tvdbId);

        if (! $series) {
            $series = Series::query()->firstOrCreate([
                'thetvdb_id' => $tvdbId,
            ], [
                'title' => self::getTitle($tvdbData),
                'thetvdb_slug' => $tvdbData['slug'],
                'poster_url' => $posterUrl,
                'year' => $tvdbData['year'],
                'status' => Status::WAITING,
                'last_updated' => now(),
            ]);
        }

        app(AniarrLogger::class)->setSeries($series->id);

        // Синхронизация RSS-лент
        foreach ($rssFeeds as $feed) {
            $bdFeed = $series->rssFeeds()->firstOrNew([
                'season_number' => $feed['season_number'] ?? 1,
            ]);

            $bdFeed->rss_url = $feed['rss_url'];

            $bdFeed->save();
        }

        if (! $series->poster_path) {
            $posterPath = DownloadPosterAction::execute($posterUrl, $series->id);
            $series->update(['poster_path' => $posterPath]);
        }

        $sonarrClient = new SonarrClient;

        if ($sonarrClient->hasSeries($tvdbId)) {
            SyncSeriesWithSonarrJob::dispatch($series->id);
        } else {
            Bus::chain([
                AddSeriesToSonarrJob::dispatch($series->id),
                SyncSeriesWithSonarrJob::dispatch($series->id),
            ]);
        }
    }

    /**
     * Определяет наилучшее название для сериала на основе данных TVDB.
     *
     * Приоритет:
     * 1. Переведённое имя из `translation.name`
     * 2. Псевдоним, соответствующий текущей локали
     * 3. Псевдоним, соответствующий резервной локали
     * 4. Оригинальное название сериала
     *
     * @param  array  $tvdbData  Массив данных сериала из TVDB
     * @return string Выбранное название
     */
    private static function getTitle(array $tvdbData): string
    {
        $title = Arr::get($tvdbData, 'translation.name');
        if ($title) {
            return $title;
        }

        $aliasTitle = '';
        $fallbackAliasTitle = '';

        $locale = app(TvdbLocaleMapper::class)->map(config('app.locale'));
        $fallbackLocale = app(TvdbLocaleMapper::class)->map(config('app.fallback_locale'));

        foreach ($tvdbData['aliases'] as $alias) {
            if ($alias['language'] == $locale) {
                $aliasTitle = $alias['name'];
            }

            if ($alias['language'] == $fallbackLocale) {
                $fallbackAliasTitle = $alias['name'];
            }
        }

        if ($aliasTitle) {
            return $aliasTitle;
        }

        if ($fallbackAliasTitle) {
            return $fallbackAliasTitle;
        }

        return $tvdbData['name'] ?? 'unknown';
    }
}
