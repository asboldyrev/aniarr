<?php

namespace App\Integrations\QBittorrent;

use App\Models\Series;
use App\Support\EpisodeNumberParser;

/**
 * Выбирает индексы файлов торрента для эпизодов, которые ещё не загружены.
 */
final class TorrentFileSelector
{
    /**
     * Определяет, какие индексы файлов торрента соответствуют отсутствующим эпизодам.
     *
     * @param  Series  $series  Модель сериала
     * @param  array  $torrentFiles  Список метаданных файлов торрента (индекс, имя)
     * @return array<int> Индексы файлов для загрузки
     */
    public function selectIndexes(Series $series, array $torrentFiles): array
    {
        $existingEpisodes = $series->episodes()->whereNotNull('downloaded_at')->pluck('episode_number')->flip()->all();
        $toDownload = [];
        foreach ($torrentFiles as $file) {
            $index = $file['index'] ?? $file['id'] ?? null;
            if ($index === null) {
                continue;
            }
            $index = (int) $index;
            $name = $file['name'] ?? '';
            $epNum = EpisodeNumberParser::fromFileName($name);

            if ($epNum !== null && ! isset($existingEpisodes[$epNum])) {
                $toDownload[] = $index;
            }
        }

        return $toDownload;
    }
}
