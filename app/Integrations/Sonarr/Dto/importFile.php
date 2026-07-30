<?php

namespace App\Integrations\Sonarr\Dto;

use Illuminate\Contracts\Support\Arrayable;
use Override;

/**
 * Объект передачи данных для импорта файла в Sonarr.
 */
class importFile implements Arrayable
{
    /**
     * @param  string  $path  Путь в файловой системе
     * @param  int  $seriesId  ID сериала в Sonarr
     * @param  int  $seasonNumber  Номер сезона
     * @param  int  $episodeId  ID эпизода в Sonarr
     */
    public function __construct(
        public readonly string $path,
        public readonly int $seriesId,
        public readonly int $seasonNumber,
        public readonly int $episodeId,
    ) {}

    /**
     * Преобразует DTO в массив, совместимый с Sonarr.
     *
     * @return array
     */
    #[Override]
    public function toArray()
    {
        return [
            'path' => $this->path,
            'seriesId' => $this->seriesId,
            'seasonNumber' => $this->seasonNumber,
            'episodeIds' => [
                $this->episodeId,
            ],
            'quality' => [
                'quality' => [
                    'id' => 8,
                ],
                'revision' => [
                    'version' => 1,
                ],
            ],
            'languages' => [
                [
                    'id' => 1,
                ],
            ],
            'indexerFlags' => 0,
            'releaseType' => 'unknown',
        ];
    }
}
