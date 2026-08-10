<?php

namespace App\Jobs;

use App\Integrations\JellyfinClient;
use App\Models\Download;
use App\Services\Logging\AniarrLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

final class RefreshJellyfinLibraryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $downloadId,
    ) {}

    public function handle(JellyfinClient $jellyfinClient): void
    {
        /** @var Download|null $download */
        $download = Download::query()
            ->with('season.series')
            ->find($this->downloadId);

        if ($download === null) {
            return;
        }

        $logger = app(AniarrLogger::class);
        $logger->setSeries($download->season->series_id);

        try {
            if (! $jellyfinClient->testConnection()) {
                $logger->warning('[Jellyfin] Обновление библиотеки пропущено: сервис недоступен');

                return;
            }

            if (! $jellyfinClient->refreshLibrary()) {
                throw new RuntimeException('Jellyfin не принял запрос на обновление библиотеки.');
            }

            $logger->info('[Jellyfin] Обновление библиотеки запущено', [
                'download_id' => $download->id,
            ]);
        } finally {
            $logger->resetSeries();
        }
    }
}
