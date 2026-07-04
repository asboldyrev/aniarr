<?php

namespace App\Enums;

enum Status: string
{
    case WAITING = 'waiting';
    case NEW_EPISODES = 'new_episodes';
    case DOWNLOADING_AVC = 'downloading_avc';
    case PROCESSING_SONARR = 'processing_sonarr';
    case DOWNLOADING_HEVC = 'downloading_hevc';
    case SYNCING_JELLYFIN = 'syncing_jellyfin';
    case DONE = 'done';
    case ERROR = 'error';

    public static function all(): array
    {
        return [
            self::WAITING,
            self::NEW_EPISODES,
            self::DOWNLOADING_AVC,
            self::PROCESSING_SONARR,
            self::DOWNLOADING_HEVC,
            self::SYNCING_JELLYFIN,
            self::DONE,
            self::ERROR,
        ];
    }

    public static function color(): string
    {
        return match (self::class) {
            self::WAITING => 'gray',
            self::NEW_EPISODES => 'blue',
            self::DOWNLOADING_AVC => 'green',
            self::PROCESSING_SONARR => 'yellow',
            self::DOWNLOADING_HEVC => 'red',
            self::SYNCING_JELLYFIN => 'purple',
            self::DONE => 'green',
            self::ERROR => 'red',
        };
    }

    public static function label(): string
    {
        return match (self::class) {
            self::WAITING => 'Ожидание',
            self::NEW_EPISODES => 'Новые эпизоды',
            self::DOWNLOADING_AVC => 'Скачивание AVC',
            self::PROCESSING_SONARR => 'Обработка Sonarr',
            self::DOWNLOADING_HEVC => 'Скачивание HEVC',
            self::SYNCING_JELLYFIN => 'Синхронизация Jellyfin',
            self::DONE => 'Готово',
            self::ERROR => 'Ошибка',
        };
    }
}
