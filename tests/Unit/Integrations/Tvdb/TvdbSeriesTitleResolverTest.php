<?php

namespace Tests\Unit\Integrations\Tvdb;

use App\Integrations\Tvdb\TvdbSeriesTitleResolver;
use Tests\TestCase;

class TvdbSeriesTitleResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.locale', 'ru');
        config()->set('app.fallback_locale', 'en');
    }

    public function test_it_uses_fallback_translation_when_primary_translation_is_missing(): void
    {
        $title = app(TvdbSeriesTitleResolver::class)->resolve([
            'name' => 'Original name',
            'translations' => [
                'rus' => [],
                'eng' => ['name' => 'English title'],
            ],
            'aliases' => [],
        ]);

        $this->assertSame('English title', $title);
    }

    public function test_it_prefers_primary_alias_before_fallback_translation(): void
    {
        $title = app(TvdbSeriesTitleResolver::class)->resolve([
            'name' => 'Original name',
            'translations' => [
                'rus' => [],
                'eng' => ['name' => 'English title'],
            ],
            'aliases' => [
                ['language' => 'rus', 'name' => 'Русский алиас'],
            ],
        ]);

        $this->assertSame('Русский алиас', $title);
    }

    public function test_it_uses_fallback_alias_when_translations_are_missing(): void
    {
        $title = app(TvdbSeriesTitleResolver::class)->resolve([
            'name' => 'Original name',
            'translations' => [],
            'aliases' => [
                ['language' => 'eng', 'name' => 'English alias'],
            ],
        ]);

        $this->assertSame('English alias', $title);
    }

    public function test_it_uses_original_name_when_no_localized_title_exists(): void
    {
        $title = app(TvdbSeriesTitleResolver::class)->resolve([
            'name' => 'Original name',
        ]);

        $this->assertSame('Original name', $title);
    }
}
