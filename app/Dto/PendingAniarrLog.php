<?php

namespace App\Dto;

use App\Enums\LogType;

class PendingAniarrLog
{
    public function __construct(
        LogType $type,
        string $message,
        array $context
    ) {
        // throw new \Exception('Not implemented');
    }
}
