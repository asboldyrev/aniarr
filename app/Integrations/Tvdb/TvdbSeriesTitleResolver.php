<?php

namespace App\Integrations\Tvdb;

use Illuminate\Support\Arr;

final class TvdbSeriesTitleResolver
{
    public function resolve(array $tvdbData): string
    {
        $localeMapper = app(TvdbLocaleMapper::class);
        $locale = $localeMapper->map(config('app.locale'));
        $fallbackLocale = $localeMapper->map(config('app.fallback_locale'));

        foreach ([$locale, $fallbackLocale] as $language) {
            $translationTitle = trim((string) Arr::get(
                $tvdbData,
                "translations.{$language}.name",
                '',
            ));

            if ($translationTitle !== '') {
                return $translationTitle;
            }

            foreach (Arr::get($tvdbData, 'aliases', []) ?: [] as $alias) {
                if (($alias['language'] ?? null) !== $language) {
                    continue;
                }

                $aliasTitle = trim((string) ($alias['name'] ?? ''));
                if ($aliasTitle !== '') {
                    return $aliasTitle;
                }
            }
        }

        $name = trim((string) Arr::get($tvdbData, 'name', ''));

        return $name !== '' ? $name : 'unknown';
    }
}
