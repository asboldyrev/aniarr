<?php

namespace App\Actions;

use App\Enums\Status;
use App\Jobs\AddSeriesToSonarrJob;
use App\Jobs\SyncSeriesWithSonarrJob;
use App\Models\Series;
use App\Services\AniarrLogger;
use App\Services\SonarrService;
use App\Services\TheTVDBService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;

final class AddSeriesAction
{
    public static function execute(int|string $tvdbId, string $rssUrl, Series|null $series = null): void
    {
        $tvdbClient = new TheTVDBService();
        $tvdbData = $tvdbClient->getSeries($tvdbId);

        $posterUrl = $tvdbClient->getPoster($tvdbId);

        if (!$series) {
            $series = Series::query()->firstOrCreate([
                'thetvdb_id' => $tvdbId,
            ], [
                'title' => self::getTitle($tvdbData),
                'thetvdb_slug' => $tvdbData['slug'],
                'rss_url' => $rssUrl,
                'poster_url' => $posterUrl,
                'year' => $tvdbData['year'],
                'status' => Status::WAITING,
            ]);
        }

        if (!$series->poster_path) {
            $posterPath = DownloadPosterAction::execute($posterUrl, $series->id);
            $series->update(['poster_path' => $posterPath]);
        }

        app(AniarrLogger::class)->setSeries($series->id);

        $sonarrClient = new SonarrService();

        if ($sonarrClient->hasSeries($tvdbId)) {
            SyncSeriesWithSonarrJob::dispatch($series->id);
        } else {
            Bus::chain([
                AddSeriesToSonarrJob::dispatch($series->id),
                SyncSeriesWithSonarrJob::dispatch($series->id)
            ]);
        }
    }

    public function __invoke(int|string $tvdbId, string $rssUrl, Series|null $series = null): void
    {
        self::execute($tvdbId, $rssUrl);
    }

    private static function getTitle(array $tvdbData): string
    {
        $title = Arr::get($tvdbData, 'translation.name');
        if ($title) {
            return $title;
        }

        $aliasTitle = '';
        $fallbackAliasTitle = '';

        $locale = app(TheTVDBService::class)->convertLocaleToTheTVDB(config('app.locale'));
        $fallbackLocale = app(TheTVDBService::class)->convertLocaleToTheTVDB(config('app.fallback_locale'));

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
