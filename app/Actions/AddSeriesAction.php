<?php

namespace App\Actions;

use App\Enums\Status;
use App\Integrations\SonarrClient;
use App\Integrations\Tvdb\TvdbClient;
use App\Integrations\Tvdb\TvdbLocaleMapper;
use App\Jobs\AddSeriesToSonarrJob;
use App\Jobs\SyncSeriesWithSonarrJob;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;

final class AddSeriesAction
{
    public function execute(int|string $tvdbId, string $rssUrl, Series|null $series = null): void
    {
        $tvdbClient = new TvdbClient();
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

        $sonarrClient = new SonarrClient();

        if ($sonarrClient->hasSeries($tvdbId)) {
            SyncSeriesWithSonarrJob::dispatch($series->id);
        } else {
            Bus::chain([
                AddSeriesToSonarrJob::dispatch($series->id),
                SyncSeriesWithSonarrJob::dispatch($series->id)
            ]);
        }
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
