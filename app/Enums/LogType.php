<?php

namespace App\Enums;

enum LogType: string
{
    case INFO = 'info';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR = 'error';

    public static function all(): array
    {
        return [
            self::INFO,
            self::SUCCESS,
            self::WARNING,
            self::ERROR,
        ];
    }

    public static function color(): string
    {
        return match (self::class) {
            self::INFO => 'gray',
            self::SUCCESS => 'green',
            self::WARNING => 'yellow',
            self::ERROR => 'red',
        };
    }

    public static function label(): string
    {
        return match (self::class) {
            self::INFO => 'Информация',
            self::SUCCESS => 'Успех',
            self::WARNING => 'Предупреждение',
            self::ERROR => 'Ошибка',
        };
    }
}
