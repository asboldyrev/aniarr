<?php

namespace App\Jobs;

use App\Actions\Downloads\CompleteImportedDownloadAction;
use App\Actions\SyncSeriesStateFromSonarrAction;
use App\Enums\DownloadStatus;
use App\Enums\LogType;
use App\Integrations\QBittorrent\Dto\Torrent as QBitTorrent;
use App\Integrations\QBittorrent\QBittorrentClient;
use App\Integrations\Sonarr\SonarrClient;
use App\Models\Download;
use App\Services\Logging\AniarrLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

final class ImportDownloadToSonarrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SONARR_COMMAND_TIMEOUT = 300;
    private const POLL_INTERVAL = 3;

    public int $tries = 5;
    public $timeout = 360;

    public function __construct(public int $downloadId) {}

    public function handle(
        SonarrClient $sonarrClient,
        QBittorrentClient $qBittorrentClient,
        SyncSeriesStateFromSonarrAction $syncAction,
        CompleteImportedDownloadAction $completeImportedDownload,
    ): void {
        $download = Download::query()->with(['season.series', 'release', 'items.episode'])->find($this->downloadId);
        if ($download === null || $download->status !== DownloadStatus::IMPORTING) {
            return;
        }

        $logger = app(AniarrLogger::class)->forDownload($download)->withSource('sonarr');

        try {
            if (! $sonarrClient->testConnection()) {
                throw new RuntimeException('Sonarr недоступен.');
            }

            if ($download->imported_at === null) {
                $current = $this->findCurrentTorrent($download, $qBittorrentClient);
                if ($current === null) {
                    throw new RuntimeException('Torrent для импорта не найден в qBittorrent.');
                }

                $commandFiles = $this->buildImportFiles($download, $current, $sonarrClient, $logger);
                if ($commandFiles === []) {
                    throw new RuntimeException('Нет файлов для ManualImport в Sonarr.');
                }

                $logger->event('download.importing', '[Sonarr] Импорт Download запущен', LogType::INFO, [
                    'files_count' => count($commandFiles),
                ]);

                $command = $sonarrClient->sendManualImportCommand($commandFiles, 'move');
                $commandId = (int) ($command['id'] ?? 0);
                if ($commandId <= 0) {
                    throw new RuntimeException('Sonarr не вернул command id для ManualImport.');
                }

                $commandResult = $this->waitForSonarrCommand($sonarrClient, $commandId);
                if ($commandResult === null) {
                    throw new RuntimeException('Таймаут ожидания ManualImport Sonarr.');
                }

                $status = (string) ($commandResult['status'] ?? '');
                if ($status !== 'completed') {
                    $logger->event(
                        'download.import_command_failed',
                        '[Sonarr] ManualImport завершился с ошибкой',
                        LogType::ERROR,
                        [
                            'command_id' => $commandId,
                            'status' => $status,
                            'message' => $commandResult['message'] ?? null,
                            'body' => $commandResult['body'] ?? null,
                            'result' => $commandResult['result'] ?? null,
                        ],
                    );

                    if ($this->completeIfSonarrAlreadyImported(
                        $download,
                        $sonarrClient,
                        $syncAction,
                        $completeImportedDownload,
                    )) {
                        return;
                    }

                    $details = $this->getSonarrCommandFailureDetails($commandResult);
                    throw new RuntimeException(
                        'ManualImport Sonarr завершился с ошибкой'.($details !== '' ? ': '.$details : '.'),
                    );
                }

                $download->update(['imported_at' => now()]);
            }

            $this->syncAndComplete($download, $sonarrClient, $syncAction, $completeImportedDownload);
        } catch (Throwable $e) {
            $logger->exception($e, event: 'download.import_failed');
            throw $e;
        }
    }

    private function findCurrentTorrent(Download $download, QBittorrentClient $qBittorrentClient): ?QBitTorrent
    {
        if (! $qBittorrentClient->login()) {
            return null;
        }

        foreach ($qBittorrentClient->getTorrentsByTag($download->qbit_tag ?? '') as $torrent) {
            if ($torrent->hash === $download->qbit_hash) {
                return $torrent;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    private function buildImportFiles(
        Download $download,
        QBitTorrent $torrent,
        SonarrClient $sonarrClient,
        AniarrLogger $logger,
    ): array {
        $seriesId = $download->season->series->sonarr_id;
        if (! $seriesId) {
            throw new RuntimeException('У Series отсутствует sonarr_id.');
        }

        $folder = $torrent->content_path !== ''
            ? $torrent->content_path
            : $torrent->save_path;

        $candidates = $sonarrClient->getManualImportCandidates($folder);
        if ($candidates === []) {
            throw new RuntimeException('Sonarr не вернул кандидатов ManualImport для папки '.$folder.'.');
        }

        $candidatePaths = [];
        $candidatesByPath = [];
        foreach ($candidates as $candidate) {
            $path = $this->normalizePath((string) ($candidate['path'] ?? ''));
            if ($path !== '') {
                $candidatePaths[] = $path;
                $candidatesByPath[$path] = $candidate;
            }
        }

        $logger->event('download.manual_import_candidates', '[Sonarr] Получены кандидаты ManualImport', LogType::INFO, [
            'folder' => $folder,
            'candidates_count' => count($candidates),
            'candidate_paths' => array_slice($candidatePaths, 0, 20),
        ]);

        $files = [];
        $savePath = rtrim($torrent->save_path, '/');

        foreach ($download->items as $item) {
            $episode = $item->episode;
            if (! $episode->sonarr_id || ! $item->torrent_file_name) {
                continue;
            }

            $path = $savePath.'/'.ltrim($item->torrent_file_name, '/');
            $candidate = $candidatesByPath[$this->normalizePath($path)] ?? null;

            if ($candidate === null) {
                throw new RuntimeException('Sonarr не распознал файл для ManualImport: '.$path);
            }

            $quality = $candidate['quality'] ?? null;
            if (! is_array($quality)) {
                throw new RuntimeException('Sonarr не определил качество файла: '.$path);
            }

            $files[] = array_filter([
                'path' => (string) ($candidate['path'] ?? $path),
                'seriesId' => $seriesId,
                'episodeIds' => [$episode->sonarr_id],
                'quality' => $quality,
                'languages' => is_array($candidate['languages'] ?? null) ? $candidate['languages'] : [],
                'releaseGroup' => $candidate['releaseGroup'] ?? null,
                'downloadId' => $candidate['downloadId'] ?? null,
                'indexerFlags' => $candidate['indexerFlags'] ?? 0,
                'releaseType' => $candidate['releaseType'] ?? 'unknown',
            ], static fn (mixed $value): bool => $value !== null);
        }

        return $files;
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    private function syncAndComplete(
        Download $download,
        SonarrClient $sonarrClient,
        SyncSeriesStateFromSonarrAction $syncAction,
        CompleteImportedDownloadAction $completeImportedDownload,
    ): void {
        $series = $download->season->series;
        $sonarrSeries = $sonarrClient->getSeriesByTvdbId($series->thetvdb_id);
        if ($sonarrSeries === null) {
            throw new RuntimeException('Сериал не найден среди добавленных сериалов Sonarr после импорта.');
        }

        $syncAction->execute($series, $sonarrSeries, $sonarrClient);

        if (! $completeImportedDownload->execute($download)) {
            throw new RuntimeException('Sonarr ещё не подтвердил импортированные файлы Download.');
        }
    }

    private function completeIfSonarrAlreadyImported(
        Download $download,
        SonarrClient $sonarrClient,
        SyncSeriesStateFromSonarrAction $syncAction,
        CompleteImportedDownloadAction $completeImportedDownload,
    ): bool {
        $series = $download->season->series;
        $sonarrSeries = $sonarrClient->getSeriesByTvdbId($series->thetvdb_id);
        if ($sonarrSeries === null) {
            return false;
        }

        $syncAction->execute($series, $sonarrSeries, $sonarrClient);

        $fresh = Download::query()->with(['release', 'items.episode'])->find($download->id);
        if ($fresh === null || $fresh->items->isEmpty()) {
            return false;
        }

        foreach ($fresh->items as $item) {
            if (! $item->episode->has_file || $item->episode->file_codec !== $fresh->release->codec) {
                return false;
            }
        }

        $fresh->update(['imported_at' => now()]);

        return $completeImportedDownload->execute($fresh);
    }

    /** @return array<string, mixed>|null */
    private function waitForSonarrCommand(SonarrClient $sonarrClient, int $commandId): ?array
    {
        $deadline = time() + self::SONARR_COMMAND_TIMEOUT;

        while (time() < $deadline) {
            sleep(self::POLL_INTERVAL);
            $command = $sonarrClient->getCommand($commandId);
            if ($command === null) {
                continue;
            }

            $status = (string) ($command['status'] ?? '');
            if (in_array($status, ['completed', 'failed'], true)) {
                return $command;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $command */
    private function getSonarrCommandFailureDetails(array $command): string
    {
        foreach (['message', 'errorMessage', 'result'] as $key) {
            $value = $command[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $body = $command['body'] ?? null;
        if (is_array($body)) {
            foreach (['message', 'errorMessage'] as $key) {
                $value = $body[$key] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        return '';
    }

    public function failed(?Throwable $e): void
    {
        $download = Download::query()->with('season')->find($this->downloadId);
        if ($download === null) {
            return;
        }

        if ($download->imported_at !== null) {
            $download->update(['error_message' => $e?->getMessage()]);

            app(AniarrLogger::class)
                ->forDownload($download)
                ->withSource('sonarr')
                ->event(
                    'download.verification_pending',
                    '[Sonarr] Импорт выполнен, ожидается подтверждение состояния',
                    LogType::WARNING,
                    ['error' => $e?->getMessage()],
                );

            SyncSeriesWithSonarrJob::dispatch($download->season->series_id)->onQueue('downloads');
            return;
        }

        $download->update([
            'status' => DownloadStatus::FAILED,
            'failed_at' => now(),
            'error_message' => $e?->getMessage(),
        ]);

        app(AniarrLogger::class)
            ->forDownload($download)
            ->withSource('sonarr')
            ->event('download.failed', '[Sonarr] Download завершился ошибкой', LogType::ERROR, [
                'error' => $e?->getMessage(),
            ]);
    }
}
