<?php

namespace App\Services\Downloads\Dto;

use App\Models\Release;

final readonly class DownloadPlan
{
    /**
     * @param  array<DownloadPlanItem>  $items
     */
    public function __construct(
        public Release $release,
        public array $items,
    ) {}
}
