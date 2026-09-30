<?php

namespace App\Services\Downloads\Dto;

use App\Enums\DownloadReason;
use App\Models\Episode;

final readonly class DownloadPlanItem
{
    public function __construct(
        public Episode $episode,
        public DownloadReason $reason,
    ) {}
}
