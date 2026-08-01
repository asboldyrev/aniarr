<?php

namespace App\Console\Commands;

use App\Actions\AddSeriesAction;
use Illuminate\Console\Command;

class AddSeriesCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'app:add-series {rss_url : ссылка на rss-ленту} {tvdb_id : TheTVDB ID} {season_number? : номер сезона (опционально)}';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Добавляет новый сериал по TVDB ID и URL RSS-ленты';

    /**
     * Выполняет консольную команду.
     *
     * Получает данные сериала из TVDB, загружает постер и запускает интеграцию с Sonarr.
     *
     * @param  AddSeriesAction  $addSeries  Действие, выполняющее добавление сериала
     * @return void
     */
    public function handle(AddSeriesAction $addSeries)
    {
        $rssUrl = $this->argument('rss_url');
        $tvdbId = $this->argument('tvdb_id');
        $seasonNumber = $this->argument('season_number');

        $rssFeeds = [[
            'rss_url' => $rssUrl,
            'season_number' => $seasonNumber ? (int) $seasonNumber : 1,
        ]];

        $addSeries->execute($tvdbId, $rssFeeds);
    }
}
