<?php

namespace App\Jobs;

use App\Events\SeriesUpdated;
use App\Integrations\QBittorrent\Dto\File;
use App\Integrations\QBittorrent\Dto\Torrent as DtoTorrent;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Models\Series;
use App\Models\Torrent;
use App\Services\Logging\AniarrLogger;
use App\Services\Rss\RssParser;
use App\Services\SeriesStatsBroadcaster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Работает в очереди "downloads": раз в секунду опрашивает qBittorrent,
 * обновляет progress сериала и шлёт на фронт. По завершении загрузки запускает импорт в Sonarr.
 */
class WatchTorrentProgressJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Максимальное количество попыток выполнения задачи */
    public int $tries = 10000;

    /** @var int Таймаут задачи (0 — без ограничения) */
    public $timeout = 0;

    /**
     * Создаёт новый экземпляр задачи.
     */
    public function __construct(
        public int $torrentId
    ) {}

    /**
     * Выполняет задачу.
     */
    public function handle(QBittorrentClient $qBittorrentClient): void
    {
        $torrent = Torrent::find($this->torrentId);
        if (! $torrent || ! $torrent->active_torrent_hash) {
            return;
        }

        if (! $qBittorrentClient->login()) {
            return;
        }

        $hash = $torrent->active_torrent_hash;

        try {
            $torrents = $qBittorrentClient->getTorrentsByTag($torrent->qbitTag());
        } catch (\Throwable $e) {
            app(AniarrLogger::class)->exception($e);
        }

        /** @var DtoTorrent $current */
        $current = array_find($torrents ?? [], fn($torrent) => ($torrent->hash ?? '') === $hash);

        if (empty($current)) {
            app(AniarrLogger::class)->warning('[Torrent] Торрент не найден в qBittorrent, выход', ['hash' => $hash]);
            $this->release(5);

            return;
        }

        $progress = (int) round((float) ($current->progress ?? 0) * 100);
        $eta = $current->eta ?? 0; // в секундах
        $state = $current->state ?? '';

        $torrent->update(['progress' => $progress, 'eta' => max(16_777_215, $eta), 'last_updated' => now()]);
        broadcast(new SeriesUpdated($torrent->series))->toOthers();
        app(SeriesStatsBroadcaster::class)->broadcast();

        $isDone = $progress >= 100 || in_array($state, ['completed', 'stalledUP', 'stoppedUp'], true);

        if (! $isDone) {
            // Динамический интервал на основе ETA торрента для предотвращения переполнения attempts (unsignedTinyInteger)
            // Формула: интервал от 1 до 15 секунд, пропорционально ETA (максимум 7200 секунд = 2 часа)
            $releaseDelay = (int) max(1, min(15, ceil($eta / 7200)));
            $this->release($releaseDelay);

            return;
        }

        app(AniarrLogger::class)->info('[Torrent] Загрузка 100%, собираем путь', [
            'hash' => $hash,
            'progress' => $progress,
            'state' => $state,
        ]);

        $contentPath = $current->content_path ?? null;
        if (empty($contentPath)) {
            $savePath = rtrim($current->save_path ?? '', '/');
            $name = $current->name ?? '';

            if (!empty($savePath) && empty($name)) {
                $contentPath = $savePath . '/' . $name;
            }
        }

        if (!empty($contentPath)) {
            $files = $qBittorrentClient->getTorrentFiles($hash);
            $files = collect($files)
                ->filter(function (File $file) {
                    return $file->priority !== 0;
                })
                ->map(function (File $file) use ($current) {
                    $file->path = $current->save_path . '/' . $file->name;

                    return $file;
                })->values()->toArray();
        }

        if (! empty($files)) {
            app(AniarrLogger::class)->info('[Torrent] Путь для Sonarr сохранён', [
                'active_download_path' => $contentPath,
                'path_length' => $contentPath !== null ? strlen($contentPath) : 0,
            ]);

            if (! empty($contentPath)) {
                $torrent->update(['active_download_path' => $contentPath, 'last_updated' => now()]);
            }

            // Запускаем только импорт в Sonarr. Удаление торрента будет выполнено после успешного импорта
            ImportDownloadToSonarrJob::dispatch($torrent->id, $files)->onQueue('downloads');
        }
    }

    /**
     * Уникальный идентификатор задачи для предотвращения дублирования.
     */
    public function uniqueId(): string
    {
        return 'watch-torrent:' . $this->torrentId;
    }

    /**
     * Время (в секундах), на которое уникальность задачи сохраняется.
     */
    public function uniqueFor(): int
    {
        return 60;
    }
}
