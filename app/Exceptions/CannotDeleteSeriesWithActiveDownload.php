<?php

namespace App\Exceptions;

use RuntimeException;

final class CannotDeleteSeriesWithActiveDownload extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Нельзя удалить сериал, пока у него есть активная загрузка.');
    }
}
