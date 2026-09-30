<?php

namespace App\Exceptions;

use RuntimeException;

final class ActiveDownloadExists extends RuntimeException
{
    public function __construct(int $seasonId)
    {
        parent::__construct("Для сезона {$seasonId} уже существует активная загрузка.");
    }
}
