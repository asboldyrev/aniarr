<?php

namespace App\Enums;

enum DownloadReason: string
{
    case MISSING = 'missing';
    case UPGRADE = 'upgrade';
    case REFRESH = 'refresh';
}
