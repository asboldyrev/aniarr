<?php

namespace App\Dto;

use Illuminate\Contracts\Support\Arrayable;

final class Statistics implements Arrayable
{
    public function __construct(
        public int $totalSeries,
        public int $activeDownloads,
        public int $waitingForUpdates,
        public int $errorsCount
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
