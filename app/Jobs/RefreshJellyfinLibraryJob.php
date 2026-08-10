<?php

namespace App\Jobs;

use App\Enums\LogType;
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

    public function __construct(public int $downloadId) {}

    public function handle(JellyfinClient $jellyfinClient): void
    {
        $download = Download::query()->with('season.series')->find($this->downloadId);
        if ($download === null) {
            return;
        }

        $logger = app(AniarrLogger::class)->forDownload($download)->withSource('jellyfin');

        if (! $jellyfinClient->testConnection()) {
            $logger->event(
                'jellyfin.unavailable',
                '[Jellyfin] Обновление библиотеки пропущено: сервис недоступен',
                LogType::WARNING,
            );
            return;
        }

        if (! $jellyfinClient->refreshLibrary()) {
            throw new RuntimeException('Jellyfin не принял запрос на обновление библиотеки.');
        }

        $logger->event(
            'jellyfin.refresh_requested',
            '[Jellyfin] Обновление библиотеки запущено',
            LogType::INFO,
        );
    }
}
