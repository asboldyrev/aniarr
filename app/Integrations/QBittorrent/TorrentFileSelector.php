<?php

namespace App\Integrations\QBittorrent;

use App\Integrations\QBittorrent\Dto\File;
use App\Models\Episode;
use App\Support\EpisodeNumberParser;
use Illuminate\Support\Collection;

/**
 * Выбирает индексы файлов торрента для эпизодов, которые ещё не загружены.
 */
final class TorrentFileSelector
{
    /**
     * Определяет, какие индексы файлов торрента соответствуют отсутствующим эпизодам.
     *
     * @param  Collection<Episode>  $series  Модель сериала
     * @param  array  $torrentFiles  Список метаданных файлов торрента (индекс, имя)
     * @return array<int> Индексы файлов для загрузки
     */
    public function selectIndexes(Collection $episodes, array $torrentFiles): array
    {
        $toDownload = [];
        /** @var File $file */
        foreach ($torrentFiles as $file) {
            $index = $file->index;
            $name = $file->name;
            $epNum = EpisodeNumberParser::fromFileName($name);

            if ($epNum !== null && $episodes->contains(fn (Episode $episode) => $episode->episode_number == $epNum)) {
                $toDownload[] = $index;
            }
        }

        return $toDownload;
    }
}
