<?php

namespace App\Services;

use App\Enums\LogType;
use App\Exceptions\BadMethodCallException;
use App\Models\Series;
use Exception;

/**
 * @method void error(string $message, array $context = [])
 * @method void warning(string $message, array $context = [])
 * @method void success(string $message, array $context = [])
 * @method void info(string $message, array $context = [])
 */
final class AniarrLogger
{
    protected Series|null $series;

    public function setSeries(Series|int $id): void
    {
        $this->series = Series::query()->find($id);
    }

    public function resetSeries(): void
    {
        $this->series = null;
    }

    public function __call(string $name, array $arguments)
    {
        $message = array_shift($arguments);
        $context = count($arguments) ? array_shift($arguments) : [];

        $type = LogType::tryFrom($name);

        if ($type === null) {
            throw new BadMethodCallException("Unknown log type: {$name}");
        }

        $this->log($type, $message, $context);
    }

    /**
     * Записать сообщение в канал aniarr с указанным уровнем.
     */
    public function log(LogType $level, string $message, array $context = []): void
    {
        $formatted = sprintf(
            "[%s] %s: %s %s\n",
            now()->format('Y-m-d H:i:s'),
            $level->name,
            $message,
            !empty($context)
                ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : ''
        );

        if (!empty($this->series)) {
            $this->series->activityLogs()->create([
                'message' => $message,
                'type' => $level,
            ]);
        }

        $logPath = storage_path('logs/aniarr.log');

        file_put_contents($logPath, $formatted, FILE_APPEND | LOCK_EX);
    }

    /**
     * Записать ошибку исключения
     */
    public function exception(Exception $exception, string|null $url = null): void
    {
        if ($url) {
            $context['url'] = $url;
        }

        $context['code'] = $exception->getCode();
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();

        $this->error($exception->getMessage(), $context);
    }
}
