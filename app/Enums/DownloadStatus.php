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

    /**
     * @return array<int, self>
     */
    public static function active(): array
    {
        return [
            self::PENDING,
            self::PREPARING,
            self::DOWNLOADING,
            self::IMPORTING,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function activeValues(): array
    {
        return array_map(
            fn (self $status): string => $status->value,
            self::active(),
        );
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }
}
