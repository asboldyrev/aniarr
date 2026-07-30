<?php

namespace App\Support;

/**
 * Парсер номера эпизода из имени файла.
 */
class EpisodeNumberParser
{
    /**
     * Извлекает номер эпизода из имени файла, используя различные паттерны.
     *
     * @param  string  $name  Имя файла (например, "Series.S01E02.mkv", "Episode [12].avi")
     * @return int|null Номер эпизода или null, если не удалось распознать
     */
    public static function fromFileName(string $name): ?int
    {
        if (preg_match('/[Ee](\d{1,4})/', $name, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/\[(\d{1,4})\]/', $name, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/\b(\d{1,2})x(\d{1,4})\b/i', $name, $m)) {
            return (int) $m[2];
        }
        if (preg_match('/\bэп?\s*(\d{1,4})\b/ui', $name, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/\bepisode\s*(\d{1,4})\b/i', $name, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
