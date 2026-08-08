<?php

namespace App\Enums;

enum LogType: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';

    public static function all(): array
    {
        return [
            self::DEBUG,
            self::INFO,
            self::WARNING,
            self::ERROR,
        ];
    }

    public static function color(): string
    {
        return match (self::class) {
            self::DEBUG => 'gray',
            self::INFO => 'green',
            self::WARNING => 'yellow',
            self::ERROR => 'red',
        };
    }

    public static function label(): string
    {
        return match (self::class) {
            self::DEBUG => 'Отладка',
            self::INFO => 'Информация',
            self::WARNING => 'Предупреждение',
            self::ERROR => 'Ошибка',
        };
    }
}
