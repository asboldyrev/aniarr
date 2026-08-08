<?php

namespace App\Jobs;

use App\Enums\Status;
use App\Events\SeriesUpdated;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Models\Series;
use App\Models\Torrent;
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
        public int $torrentId
    ) {}

    /**
     * Execute the job.
     *
     * @param  QBittorrentClient  $qBittorrentClient  Экземпляр клиента QBittorrent
     */
    public function handle(QBittorrentClient $qBittorrentClient): void
    {
        $torrent = Torrent::find($this->torrentId);
        if (! $torrent) {
            app(AniarrLogger::class)->warning('[QBittorrent] Сериал не найден', ['series_id' => $this->torrentId]);

            return;
        }

        $hash = $torrent->active_torrent_hash;
        $hasHash = $hash !== null && $hash !== '';

        app(AniarrLogger::class)->info('[QBittorrent] Старт удаления', [
            'hash' => $hash,
            'hash_empty' => ! $hasHash,
        ]);

        if ($hasHash && $qBittorrentClient->login()) {
            $deleted = $qBittorrentClient->deleteTorrent($hash);
            $qBittorrentClient->deleteTags($torrent->qbitTag());
            app(AniarrLogger::class)->info('[QBittorrent] Удаление торрента из qBittorrent', [
                'hash' => $hash,
                'delete_files' => true,
                'result' => $deleted,
            ]);
        } else {
            app(AniarrLogger::class)->warning('[QBittorrent] Пропуск удаления (нет hash или qBit не залогинен)');
        }

        $torrent->update([
            'downloaded' => true,
            'active_torrent_hash' => null,
        ]);
        broadcast(new SeriesUpdated($torrent->series))->toOthers();
        SeriesStatsBroadcaster::broadcast();
    }
}
