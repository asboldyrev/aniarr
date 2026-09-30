<?php

namespace App\Jobs;

use App\Actions\Downloads\CleanupDownloadTorrentAction;
use App\Models\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CleanupQBitTorrentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $downloadId) {}

    public function handle(CleanupDownloadTorrentAction $cleanupTorrent): void
    {
        $download = Download::query()->find($this->downloadId);
        if ($download === null) {
            return;
        }

        $cleanupTorrent->execute($download);
    }
}
