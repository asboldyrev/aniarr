<?php

namespace App\Actions\Downloads;

use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Jobs\CleanupQBitTorrentJob;
use App\Jobs\PlanSeasonDownloadsJob;
use App\Jobs\RefreshJellyfinLibraryJob;
use App\Models\Download;
use App\Services\Logging\AniarrLogger;
use Illuminate\Support\Facades\DB;

final class CompleteImportedDownloadAction
{
    public function execute(Download $download): bool
    {
        return DB::transaction(function () use ($download): bool {
            /** @var Download|null $lockedDownload */
            $lockedDownload = Download::query()
                ->with(['release', 'items.episode', 'season'])
                ->lockForUpdate()
                ->find($download->id);

            if (
                $lockedDownload === null
                || $lockedDownload->status !== DownloadStatus::IMPORTING
                || $lockedDownload->imported_at === null
            ) {
                return false;
            }

            foreach ($lockedDownload->items as $item) {
                if (! $item->episode->has_file) {
                    return false;
                }

                if ($item->episode->file_codec !== $lockedDownload->release->codec) {
                    return false;
                }
            }

            $lockedDownload->update([
                'status' => DownloadStatus::COMPLETED,
                'progress' => 100,
                'completed_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);

            app(AniarrLogger::class)
                ->forDownload($lockedDownload)
                ->withSource('sonarr')
                ->event(
                    'download.completed',
                    '[Sonarr] Download успешно импортирован',
                    LogType::INFO,
                    ['release_id' => $lockedDownload->release_id],
                );

            CleanupQBitTorrentJob::dispatch($lockedDownload->id)
                ->onQueue('downloads')
                ->afterCommit();
            RefreshJellyfinLibraryJob::dispatch($lockedDownload->id)
                ->onQueue('downloads')
                ->afterCommit();
            PlanSeasonDownloadsJob::dispatch($lockedDownload->season_id)
                ->onQueue('downloads')
                ->afterCommit();

            return true;
        });
    }
}
