<?php

namespace App\Integrations\Tvdb;

final class TvdbLocaleMapper
{
    private const LANGUAGE_CODES = [
        'en' => 'eng',
        'ru' => 'rus',
        'ja' => 'jpn',
        'fr' => 'fra',
        'es' => 'spa',
        'de' => 'deu',
        'it' => 'ita',
        'pt' => 'por',
        'zh' => 'zho',
        'ko' => 'kor',
    ];

    public function map(string $locale): string
    {
        $baseLocale = strtolower(
            preg_split('/[-_]/', $locale)[0],
        );

        return self::LANGUAGE_CODES[$baseLocale] ?? 'eng';
    }
}
