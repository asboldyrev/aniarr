<?php

namespace App\Integrations\Sonarr\Dto;

class MediaInfo
{
    public function __construct(
        public readonly int $audioBitrate,
        public readonly int $audioChannels,
        public readonly string $audioCodec,
        public readonly string $audioLanguages,
        public readonly int $audioStreamCount,
        public readonly int $videoBitDepth,
        public readonly int $videoBitrate,
        public readonly string $videoCodec,
        public readonly float|int $videoFps,
        public readonly string $videoDynamicRange,
        public readonly string $videoDynamicRangeType,
        public readonly string $resolution,
        public readonly string $runTime,
        public readonly string $scanType,
        public readonly string $subtitles,
    ) {}

    public static function makeFromResponse(array $response): self
    {
        return new self(
            audioBitrate: (int) ($response['audioBitrate'] ?? 0),
            audioChannels: (int) ($response['audioChannels'] ?? 0),
            audioCodec: $response['audioCodec'] ?? '',
            audioLanguages: $response['audioLanguages'] ?? '',
            audioStreamCount: (int) ($response['audioStreamCount'] ?? 0),
            videoBitDepth: (int) ($response['videoBitDepth'] ?? 0),
            videoBitrate: (int) ($response['videoBitrate'] ?? 0),
            videoCodec: $response['videoCodec'] ?? '',
            videoFps: (float) ($response['videoFps'] ?? 0),
            videoDynamicRange: $response['videoDynamicRange'] ?? '',
            videoDynamicRangeType: $response['videoDynamicRangeType'] ?? '',
            resolution: $response['resolution'] ?? '',
            runTime: $response['runTime'] ?? '',
            scanType: $response['scanType'] ?? '',
            subtitles: $response['subtitles'] ?? '',
        );
    }
}
