<?php

namespace App\Enums;

enum DownloadTrigger: string
{
    case AUTOMATIC = 'automatic';
    case MANUAL = 'manual';
}
