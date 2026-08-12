<?php

namespace App\Actions\Downloads;

use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Models\Download;
use App\Services\Logging\AniarrLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class CancelDownloadAction
{
    public function __construct(
        private readonly QBittorrentClient $qBittorrentClient,
    ) {}

    public function execute(Download $download): Download
    {
        return DB::transaction(function () use ($download): Download {
            /** @var Download|null $lockedDownload */
            $lockedDownload = Download::query()
                ->with('season.series')
                ->lockForUpdate()
                ->find($download->id);

            if ($lockedDownload === null) {
                throw new InvalidArgumentException('Download не найден.');
            }

            if (! in_array($lockedDownload->status, [
                DownloadStatus::PENDING,
                DownloadStatus::PREPARING,
                DownloadStatus::DOWNLOADING,
            ], true)) {
                throw new InvalidArgumentException('Этот Download уже нельзя отменить.');
            }

            if ($lockedDownload->qbit_hash) {
                if (! $this->qBittorrentClient->deleteTorrent($lockedDownload->qbit_hash)) {
                    throw new RuntimeException('Не удалось удалить torrent из qBittorrent. Download не отменён.');
                }
            }

            $lockedDownload->update([
                'status' => DownloadStatus::CANCELLED,
                'eta_seconds' => 0,
                'error_message' => null,
            ]);

            app(AniarrLogger::class)
                ->forDownload($lockedDownload)
                ->withSource('aniarr')
                ->event('download.cancelled', '[Aniarr] Download отменён пользователем', LogType::INFO);

            return $lockedDownload->fresh(['season.series', 'release', 'items.episode']);
        });
    }
}
