<?php

namespace App\Actions\Torrents;

use App\Models\Series;
use App\Models\Torrent;
use App\Services\Rss\Dto\FeedItem;

final class SaveTorrent
{
    public function execute(Series $series, FeedItem $item): Torrent
    {
        return Torrent::updateOrCreate(
            ['guid' => $item->guid],
            [
                'series_id' => $series->id,
                'torrent_url' => $item->torrentUrl,
                'torrent_id' => $item->torrentId,
                'codec' => $item->codec,
                'episodes' => $item->episodes,
                'size' => $item->size,
            ],
        );
    }
}
