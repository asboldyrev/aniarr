<?php

namespace App\Actions;

use App\Integrations\Sonarr\SonarrClient;
use App\Integrations\Tvdb\TvdbClient;
use App\Integrations\Tvdb\TvdbSeriesTitleResolver;
use App\Jobs\AddSeriesToSonarrJob;
use App\Jobs\SyncSeriesWithSonarrJob;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
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
                'title' => app(TvdbSeriesTitleResolver::class)->resolve($tvdbData),
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
}
