<?php

namespace App\Support\Formatting;

final class TransferFormatter
{
    public static function speed(int $bytesPerSecond): string
    {
        if ($bytesPerSecond >= 1024 ** 2) {
            return round($bytesPerSecond / 1024 ** 2, 1).' MB/s';
        }

        if ($bytesPerSecond >= 1024) {
            return round($bytesPerSecond / 1024, 1).' KB/s';
        }

        return $bytesPerSecond.' B/s';
    }

    public static function eta(int $seconds): ?string
    {
        if ($seconds < 0) {
            return null;
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $seconds %= 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
