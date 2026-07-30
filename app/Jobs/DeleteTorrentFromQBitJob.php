<?php

namespace App\Jobs;

use App\Enums\Status;
use App\Events\SeriesUpdated;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Models\Series;
use App\Services\Logging\AniarrLogger;
use App\Services\SeriesStatsBroadcaster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Второе звено chain: после того как Sonarr импортировал файлы — удалить торрент из qBittorrent
 * вместе с файлами и папками на диске, обновить состояние сериала.
 */
class DeleteTorrentFromQBitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  int  $seriesId  Идентификатор сериала
     */
    public function __construct(
        public int $seriesId
    ) {}

    /**
     * Execute the job.
     *
     * @param  QBittorrentClient  $qBittorrentClient  Экземпляр клиента QBittorrent
     */
    public function handle(QBittorrentClient $qBittorrentClient): void
    {
        $series = Series::find($this->seriesId);
        if (! $series) {
            app(AniarrLogger::class)->warning('[DeleteQBit] Сериал не найден', ['series_id' => $this->seriesId]);

            return;
        }

        $hash = $series->active_torrent_hash;
        $hasHash = $hash !== null && $hash !== '';

        app(AniarrLogger::class)->info('[DeleteQBit] Старт', [
            'hash' => $hash,
            'hash_empty' => ! $hasHash,
        ]);

        if ($hasHash && $qBittorrentClient->login()) {
            $deleted = $qBittorrentClient->deleteTorrent($hash);
            $qBittorrentClient->deleteTags($series->qbitTag());
            app(AniarrLogger::class)->info('[DeleteQBit] Удаление торрента из qBittorrent', [
                'hash' => $hash,
                'delete_files' => true,
                'result' => $deleted,
            ]);
        } else {
            app(AniarrLogger::class)->info('[DeleteQBit] Пропуск удаления (нет hash или qBit не залогинен)');
        }

        $series->update([
            'active_torrent_hash' => null,
            'active_download_path' => null,
            'active_download_is_hevc' => false,
            'progress' => null,
            'eta' => null,
            'status' => Status::DONE,
            'last_updated' => now(),
        ]);
        broadcast(new SeriesUpdated($series->fresh()))->toOthers();
        SeriesStatsBroadcaster::broadcast();
    }
}
