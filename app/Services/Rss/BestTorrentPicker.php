<?php

namespace App\Services\Rss;

use App\Services\Rss\Dto\FeedItem;
use App\Services\Rss\Dto\FeedItems;

/**
 * Сервис выбора наилучшего торрента из RSS-ленты по критериям (кодек, свежесть эпизодов).
 */
class BestTorrentPicker
{
    /**
     * Выбрать один торрент для загрузки: предпочитаем HEVC, иначе AVC.
     * Предпочитаем элемент с максимальным диапазоном эпизодов (самые свежие).
     *
     * @param  FeedItems  $feedItems  Коллекция элементов RSS-ленты
     * @return FeedItem|null Выбранный элемент или null, если ничего не подходит
     */
    public function pick(FeedItems $feedItems): ?FeedItem
    {
        if (empty($feedItems)) {
            return null;
        }

        $byMaxEpisode = [];
        /** @var FeedItem $item */
        foreach ($feedItems->items as $item) {
            $maxEpisode = max($item->episodes);
            if (! isset($byMaxEpisode[$maxEpisode])) {
                $byMaxEpisode[$maxEpisode] = [];
            }
            $byMaxEpisode[$maxEpisode][] = $item;
        }

        ksort($byMaxEpisode);
        $latest = end($byMaxEpisode);

        /** @var FeedItem $item */
        foreach ($latest as $item) {
            if (strtolower($item->codec) == 'hevc') {
                return $item;
            }
        }

        /** @var FeedItem $item */
        foreach ($latest as $item) {
            if (strtolower($item->codec) == 'avc') {
                return $item;
            }
        }

        return $latest[0] ?? null;
    }
}
