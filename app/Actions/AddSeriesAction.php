<?php

namespace App\Actions;

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
 * Получает данные сериала из TVDB, сохраняет сезоны/RSS и запускает интеграцию с Sonarr.
 */
final class AddSeriesAction
{
    public function execute(int|string $tvdbId, array $rssFeeds, ?Series $series = null): void
    {
        $tvdbClient = new TvdbClient;
        $tvdbData = $tvdbClient->getSeries($tvdbId);
        $posterUrl = $tvdbClient->getPoster($tvdbId);

        if ($series === null) {
            $series = Series::query()->firstOrCreate([
                'thetvdb_id' => $tvdbId,
            ], [
                'title' => self::getTitle($tvdbData),
                'thetvdb_slug' => $tvdbData['slug'],
                'poster_url' => $posterUrl,
                'year' => $tvdbData['year'],
            ]);
        }

        $logger = app(AniarrLogger::class);
        $logger->setSeries($series->id);

        foreach ($rssFeeds as $feed) {
            $season = $series->seasons()->firstOrCreate([
                'number' => (int) ($feed['season_number'] ?? 1),
            ]);

            $season->rssFeed()->updateOrCreate([], [
                'rss_url' => $feed['rss_url'],
            ]);
        }

        if (! $series->poster_path) {
            $posterPath = DownloadPosterAction::execute($posterUrl, $series->id);
            $series->update(['poster_path' => $posterPath]);
        }

        $sonarrClient = new SonarrClient;

        if ($sonarrClient->hasSeries((int) $tvdbId)) {
            SyncSeriesWithSonarrJob::dispatch($series->id);
        } else {
            Bus::chain([
                new AddSeriesToSonarrJob($series->id),
                new SyncSeriesWithSonarrJob($series->id),
            ])->dispatch();
        }

        $logger->resetSeries();
    }

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
