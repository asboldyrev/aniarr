<?php

namespace App\Enums;

enum Codec: string
{
    case HEVC = 'hevc';
    case AVC = 'avc';

    public static function all(): array
    {
        return [
            self::HEVC,
            self::AVC,
        ];
    }

    public static function color(): string
    {
        return match (self::class) {
            self::AVC => 'gray',
            self::HEVC => 'blue',
        };
    }
}
