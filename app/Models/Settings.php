<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Settings extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getAll(): Collection
    {
        $result = [];

        foreach (self::all() as $item) {
            $result[$item->key] = $item->value;
        }

        return collect($result);
    }

    /**
     * Получить значение настройки по ключу
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::query()->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Установить значение настройки
     */
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(compact('key', 'value'));
    }
}
