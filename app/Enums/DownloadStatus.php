<?php

namespace App\Enums;

enum DownloadStatus: string
{
    case PENDING = 'pending';
    case PREPARING = 'preparing';
    case DOWNLOADING = 'downloading';
    case IMPORTING = 'importing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}
