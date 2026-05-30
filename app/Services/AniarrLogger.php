<?php

namespace App\Services;

use App\Enums\LogType;
use App\Exceptions\BadMethodCallException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @method static void error(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void success(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 */
class AniarrLogger
{
    public static function __callStatic(string $name, array $arguments)
    {
        $message = array_shift($arguments);
        $context = count($arguments) ? array_shift($arguments) : [];

        $type = LogType::tryFrom($name);

        if ($type === null) {
            throw new BadMethodCallException("Unknown log type: {$name}");
        }

        self::log($type, $message, $context);
    }

    /**
     * Записать сообщение в канал aniarr с указанным уровнем.
     */
    public static function log(LogType $level, string $message, array $context = []): void
    {
        // TODO добавить логирование в БД

        $formatted = sprintf(
            "[%s] %s: %s %s\n",
            now()->format('Y-m-d H:i:s'),
            $level->name,
            $message,
            !empty($context)
                ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : ''
        );

        $logPath = storage_path('logs/aniarr.log');

        file_put_contents($logPath, $formatted, FILE_APPEND | LOCK_EX);
    }

    /**
     * Записать ошибку неудачного HTTP-запроса.
     */
    public static function failedRequest(string $url, string $error): void
    {
        self::error(sprintf(
            'Запрос: %s не выполнен. Ошибка: %s',
            $url,
            $error,
        ));
    }

    /**
     * Записать ошибку исключения при выполнении HTTP-запроса.
     */
    public static function requestException(string $url, Throwable $exception): void
    {
        self::error(sprintf(
            'Запрос: %s не выполнен. Ошибка: %s',
            $url,
            $exception->getMessage()
        ));
    }
}
