<?php

namespace App\Jobs;

use App\Actions\SyncRssFeedAction;
use App\Events\RssFeedUpdated;
use App\Models\RssFeed;
use App\Services\Logging\AniarrLogger;
use App\Services\Rss\RssParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

final class SyncRssFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $rssFeedId,
    ) {}

    public function handle(RssParser $parser, SyncRssFeedAction $syncAction): void
    {
        /** @var RssFeed|null $rssFeed */
        $rssFeed = RssFeed::query()
            ->with('season.series')
            ->find($this->rssFeedId);

        if ($rssFeed === null || ! $rssFeed->enabled) {
            return;
        }

        $logger = app(AniarrLogger::class);
        $logger->setSeries($rssFeed->season->series_id);

        $rssFeed->update([
            'last_rss_check' => now(),
        ]);

        try {
            $items = $parser->parseFeed($rssFeed->rss_url);

            if ($items->items === []) {
                throw new RuntimeException('RSS feed does not contain supported releases.');
            }

            $changed = $syncAction->execute($rssFeed, $items);

            if ($changed) {
                event(new RssFeedUpdated(
                    rssFeedId: $rssFeed->id,
                    seasonId: $rssFeed->season_id,
                ));
            }

            $logger->info(
                $changed ? '[RSS] Лента обновлена' : '[RSS] Лента не изменилась',
                [
                    'rss_feed_id' => $rssFeed->id,
                    'season' => $rssFeed->season->number,
                    'releases_count' => count($items->items),
                ],
            );
        } catch (Throwable $e) {
            $rssFeed->update([
                'last_error_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            $logger->exception($e, $rssFeed->rss_url);

            throw $e;
        }
    }
}
