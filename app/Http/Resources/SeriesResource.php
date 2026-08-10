<?php

namespace App\Http\Resources;

use App\Enums\Codec;
use App\Enums\DownloadStatus;
use App\Models\Download;
use App\Models\Episode;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SeriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'seasons.rssFeed',
            'seasons.episodes',
            'seasons.downloads',
        ]);

        /** @var Collection<int, Download> $downloads */
        $downloads = $this->seasons->flatMap(
            fn(Season $season): Collection => $season->downloads,
        );

        /** @var Collection<int, Episode> $episodes */
        $episodes = $this->seasons->flatMap(
            fn(Season $season): Collection => $season->episodes,
        );

        $activeDownload = $downloads
            ->sortByDesc('id')
            ->first(fn(Download $download): bool => in_array($download->status, [
                DownloadStatus::PENDING,
                DownloadStatus::PREPARING,
                DownloadStatus::DOWNLOADING,
                DownloadStatus::IMPORTING,
            ], true));

        $lastFailedDownload = $downloads
            ->sortByDesc('id')
            ->first(fn(Download $download): bool => $download->status === DownloadStatus::FAILED);

        $rssFeeds = $this->seasons
            ->filter(fn(Season $season): bool => $season->rssFeed !== null)
            ->map(function (Season $season): array {
                $feed = $season->rssFeed;

                return [
                    'id' => $feed->id,
                    'seasonNumber' => $season->number,
                    'rssUrl' => $feed->rss_url,
                    'enabled' => $feed->enabled,
                    'lastRssHash' => $feed->last_rss_hash,
                    'lastRssCheck' => $feed->last_rss_check?->toIso8601String(),
                    'lastRssSuccessAt' => $feed->last_rss_success_at?->toIso8601String(),
                    'lastErrorAt' => $feed->last_error_at?->toIso8601String(),
                    'lastError' => $feed->last_error,
                ];
            })
            ->values();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'thetvdbId' => $this->thetvdb_id,
            'thetvdbSlug' => $this->thetvdb_slug,
            'posterUrl' => $this->poster_path
                ? asset('storage/'.$this->poster_path)
                : $this->poster_url,
            'year' => $this->year,
            'monitored' => $this->monitored,
            'sonarrId' => $this->sonarr_id,
            'lastSonarrSyncAt' => $this->last_sonarr_sync_at?->toIso8601String(),

            // Совместимость со старым payload до обновления frontend.
            'status' => $activeDownload?->status->value,
            'progress' => $activeDownload?->progress,
            'eta' => $activeDownload?->eta_seconds,
            'hasAvc' => $episodes->contains(
                fn(Episode $episode): bool => $episode->has_file && $episode->file_codec === Codec::AVC,
            ),
            'hasHevc' => $episodes->contains(
                fn(Episode $episode): bool => $episode->has_file && $episode->file_codec === Codec::HEVC,
            ),
            'lastEpisodes' => [
                'avc' => $this->lastEpisodeForCodec($episodes, Codec::AVC),
                'hevc' => $this->lastEpisodeForCodec($episodes, Codec::HEVC),
            ],
            'lastUpdated' => $this->updated_at?->toIso8601String(),
            'errorMessage' => $lastFailedDownload?->error_message,
            'sonarrConnected' => $this->sonarr_id !== null,
            'rssFeeds' => $rssFeeds,

            'seasons' => $this->seasons
                ->sortBy('number')
                ->map(function (Season $season): array {
                    $activeDownload = $season->downloads
                        ->sortByDesc('id')
                        ->first(fn(Download $download): bool => in_array($download->status, [
                            DownloadStatus::PENDING,
                            DownloadStatus::PREPARING,
                            DownloadStatus::DOWNLOADING,
                            DownloadStatus::IMPORTING,
                        ], true));

                    return [
                        'id' => $season->id,
                        'number' => $season->number,
                        'monitored' => $season->monitored,
                        'episodesCount' => $season->episodes->count(),
                        'filesCount' => $season->episodes->where('has_file', true)->count(),
                        'activeDownload' => $activeDownload === null ? null : [
                            'id' => $activeDownload->id,
                            'status' => $activeDownload->status->value,
                            'progress' => $activeDownload->progress,
                            'etaSeconds' => $activeDownload->eta_seconds,
                        ],
                        'rssFeed' => $season->rssFeed === null ? null : [
                            'id' => $season->rssFeed->id,
                            'rssUrl' => $season->rssFeed->rss_url,
                            'enabled' => $season->rssFeed->enabled,
                            'lastRssHash' => $season->rssFeed->last_rss_hash,
                            'lastRssCheck' => $season->rssFeed->last_rss_check?->toIso8601String(),
                        ],
                    ];
                })
                ->values(),
        ];
    }

    /**
     * @param  Collection<int, Episode>  $episodes
     */
    private function lastEpisodeForCodec(Collection $episodes, Codec $codec): ?int
    {
        $number = $episodes
            ->filter(fn(Episode $episode): bool => $episode->has_file && $episode->file_codec === $codec)
            ->max('episode_number');

        return $number === null ? null : (int) $number;
    }

    /**
     * Преобразовать коллекцию в массив
     */
    public static function collection($resource): AnonymousResourceCollection
    {
        return parent::collection($resource);
    }
}
