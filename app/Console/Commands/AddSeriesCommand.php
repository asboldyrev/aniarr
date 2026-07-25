<?php

namespace App\Console\Commands;

use App\Actions\AddSeries;
use App\Actions\AddSeriesAction;
use App\Actions\DownloadPoster;
use App\Enums\Status;
use App\Jobs\AddSeriesToSonarrJob;
use App\Jobs\SyncSeriesWithSonarrJob;
use App\Models\Series;
use App\Services\AniarrLogger;
use App\Services\RssParserService;
use App\Services\SonarrService;
use App\Services\TheTVDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class AddSeriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-series {rss_url : ссылка на rss-ленту} {tvdb_id : TheTVDB ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(AddSeriesAction $addSeries)
    {
        $rssUrl = $this->argument('rss_url');
        $tvdbId = $this->argument('tvdb_id');

        $addSeries($tvdbId, $rssUrl);
    }
}
