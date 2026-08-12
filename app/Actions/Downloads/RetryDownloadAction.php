<?php

namespace App\Actions\Downloads;

use App\Enums\DownloadStatus;
use App\Jobs\PrepareDownloadJob;
use App\Models\Download;
use App\Models\Season;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RetryDownloadAction
{
    public function execute(Download $download): Download
    {
        return DB::transaction(function () use ($download): Download {
            /** @var Download|null $source */
            $source = Download::query()
                ->with(['release', 'items.episode'])
                ->lockForUpdate()
                ->find($download->id);

            if ($source === null) {
                throw new InvalidArgumentException('Download не найден.');
            }

            if (! in_array($source->status, [DownloadStatus::FAILED, DownloadStatus::CANCELLED], true)) {
                throw new InvalidArgumentException('Повторить можно только failed или cancelled Download.');
            }

            /** @var Season|null $season */
            $season = Season::query()
                ->lockForUpdate()
                ->find($source->season_id);

            if ($season === null) {
                throw new InvalidArgumentException('Season для Download не найден.');
            }

            if ($season->downloads()->whereIn('status', DownloadStatus::activeValues())->exists()) {
                throw new InvalidArgumentException('Для сезона уже существует активный Download.');
            }

            if ($source->release === null || $source->items->isEmpty()) {
                throw new InvalidArgumentException('Исходный Download не содержит данных для повторной попытки.');
            }

            /** @var Download $retry */
            $retry = $season->downloads()->create([
                'release_id' => $source->release_id,
                'trigger' => $source->trigger,
                'status' => DownloadStatus::PENDING,
                'progress' => 0,
                'queued_at' => now(),
            ]);

            $retry->update([
                'qbit_tag' => 'aniarr-download-'.$retry->id,
            ]);

            foreach ($source->items as $item) {
                $retry->items()->create([
                    'episode_id' => $item->episode_id,
                    'reason' => $item->reason,
                ]);
            }

            PrepareDownloadJob::dispatch($retry->id)
                ->onQueue('downloads')
                ->afterCommit();

            return $retry->load(['season.series', 'release', 'items.episode']);
        });
    }
}
