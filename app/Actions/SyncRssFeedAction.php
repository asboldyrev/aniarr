<?php

namespace App\Actions;

use App\Models\RssFeed;
use App\Services\Rss\Dto\FeedItem;
use App\Services\Rss\Dto\FeedItems;
use App\Services\Rss\FeedFingerprint;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class SyncRssFeedAction
{
    public function __construct(
        private readonly FeedFingerprint $fingerprint,
    ) {}

    /**
     * Сохраняет текущее состояние RSS-ленты.
     *
     * @return bool true, если fingerprint ленты изменился
     */
    public function execute(RssFeed $rssFeed, FeedItems $items): bool
    {
        $hash = $this->fingerprint->make($items);
        $checkedAt = now();

        if ($rssFeed->last_rss_hash === $hash) {
            $rssFeed->update([
                'last_rss_check' => $checkedAt,
                'last_rss_success_at' => $checkedAt,
                'last_error_at' => null,
                'last_error' => null,
            ]);

            return false;
        }

        DB::transaction(function () use ($rssFeed, $items, $hash, $checkedAt): void {
            /** @var FeedItem $item */
            foreach ($items->items as $item) {
                if ($item->episodes === []) {
                    continue;
                }

                $rssFeed->releases()->updateOrCreate(
                    ['guid' => $item->guid],
                    [
                        'torrent_id' => $item->torrentId,
                        'release_id' => $item->releaseId,
                        'title' => $item->title,
                        'torrent_url' => $item->torrentUrl,
                        'codec' => strtolower($item->codec),
                        'quality' => $item->quality,
                        'first_episode' => min($item->episodes),
                        'last_episode' => max($item->episodes),
                        'size_bytes' => $item->size > 0 ? $item->size : null,
                        'published_at' => $item->pubDate !== '' ? Carbon::parse($item->pubDate) : null,
                    ],
                );
            }

            $rssFeed->update([
                'last_rss_hash' => $hash,
                'last_rss_check' => $checkedAt,
                'last_rss_success_at' => $checkedAt,
                'last_error_at' => null,
                'last_error' => null,
            ]);
        });

        return true;
    }
}
