<?php

namespace App\Jobs;

use App\Events\SeriesUpdated;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Models\Series;
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
        public int $seriesId
    ) {}

    /**
     * Выполняет задачу.
     */
    public function handle(QBittorrentClient $qBittorrentClient, RssParser $rssParser): void
    {
        $series = Series::find($this->seriesId);
        if (! $series || ! $series->active_torrent_hash) {
            return;
        }

        if (! $qBittorrentClient->login()) {
            return;
        }

        $hash = $series->active_torrent_hash;

        try {
            $torrents = $qBittorrentClient->getTorrentsByTag($series->qbitTag());
        } catch (\Throwable $e) {
            app(AniarrLogger::class)->exception($e);
        }

        $torrent = array_shift($torrents) ?? null;

        $current = null;
        if (($torrent['hash'] ?? '') === $hash) {
            $current = $torrent;
        }

        if ($current === null) {
            app(AniarrLogger::class)->info('[WatchTorrent] Торрент не найден в qBittorrent, выход', ['hash' => $hash]);
            $this->release(5);

            return;
        }

        $progress = (int) round((float) ($current['progress'] ?? 0) * 100);
        $eta = $current['eta'] ?? 0; // в секундах
        $state = $current['state'] ?? '';

        $series->update(['progress' => $progress, 'eta' => $eta, 'last_updated' => now()]);
        broadcast(new SeriesUpdated($series->fresh()))->toOthers();
        app(SeriesStatsBroadcaster::class)->broadcast();

        $isDone = $progress >= 100 || in_array($state, ['completed', 'stalledUP', 'stoppedUp'], true);

        if (! $isDone) {
            // Динамический интервал на основе ETA торрента для предотвращения переполнения attempts (unsignedTinyInteger)
            // Формула: интервал от 1 до 15 секунд, пропорционально ETA (максимум 7200 секунд = 2 часа)
            $releaseDelay = (int) max(1, min(15, ceil($eta / 7200)));
            $this->release($releaseDelay);

            return;
        }

        app(AniarrLogger::class)->info('[WatchTorrent] Загрузка 100%, собираем путь', [
            'hash' => $hash,
            'progress' => $progress,
            'state' => $state,
        ]);

        $contentPath = $current['content_path'] ?? null;
        if ($contentPath === null || $contentPath === '') {
            $savePath = rtrim($current['save_path'] ?? '', '/');
            $name = $current['name'] ?? '';
            if ($savePath !== '' && $name !== '') {
                $contentPath = $savePath . '/' . $name;
            }
        }

        if ($contentPath !== null && $contentPath !== '') {
            $files = $qBittorrentClient->getTorrentFiles($hash);
            $files = collect($files)
                ->filter(function (array $file) {
                    return $file['priority'] !== 0;
                })
                ->map(function (array $file) use ($current) {
                    $file['path'] = $current['save_path'] . '/' . $file['name'];

                    return $file;
                })->values()->toArray();
        }

        if (! empty($files)) {
            app(AniarrLogger::class)->info('[WatchTorrent] Путь для Sonarr сохранён', [
                'active_download_path' => $contentPath,
                'path_length' => $contentPath !== null ? strlen($contentPath) : 0,
            ]);

            if (! empty($contentPath)) {
                $series->update(['active_download_path' => $contentPath, 'last_updated' => now()]);
            }

            // Запускаем только импорт в Sonarr. Удаление торрента будет выполнено после успешного импорта
            ImportDownloadToSonarrJob::dispatch($series->id, $files)->onQueue('downloads');
        }
    }

    /**
     * Уникальный идентификатор задачи для предотвращения дублирования.
     */
    public function uniqueId(): string
    {
        return 'watch-torrent:' . $this->seriesId;
    }

    /**
     * Время (в секундах), на которое уникальность задачи сохраняется.
     */
    public function uniqueFor(): int
    {
        return 60;
    }
}
