<?php

namespace App\Services\Logging;

use App\Enums\LogType;
use App\Exceptions\BadMethodCallException;
use App\Models\Series;
use Error;
use Exception;
use Illuminate\Support\Str;

/**
 * @method void error(string $message, Exception|Error|array $context = [])
 * @method void warning(string $message, Exception|Error|array $context = [])
 * @method void success(string $message, Exception|Error|array $context = [])
 * @method void info(string $message, Exception|Error|array $context = [])
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
    public function log(LogType $level, string $message, Exception|Error|array $context = []): void
    {
        if (is_array($context) && !empty($context)) {
            $context = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_array($context)) {
            $context = '';
        } else {
            $context = implode("\n", $this->exceptionToContext($context));
        }

        if ($context) {
            $formatted = sprintf(
                "[%s] %s: %s\n%s\n",
                now()->format('Y-m-d H:i:s'),
                $level->name,
                $message,
                $context
            );
        } else {
            $formatted = sprintf(
                "[%s] %s: %s",
                now()->format('Y-m-d H:i:s'),
                $level->name,
                $message
            );
        }


        if (!empty($this->series)) {
            $this->series->activityLogs()->create([
                'message' => Str::limit($message, 250),
                'type' => $level,
            ]);
        }

        $logPath = storage_path('logs/aniarr.log');

        file_put_contents($logPath, "$formatted\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Записать ошибку исключения
     */
    public function exception(Exception|Error $exception, string|null $url = null): void
    {
        if ($url) {
            $context['url'] = $url;
        }

        $context['exception'] = implode("\n", $this->exceptionToContext($exception, short: true));

        $this->error("Code: {$exception->getCode()}, {$exception->getMessage()}", $context);
    }

    private function exceptionToContext(Exception|Error $exception, bool $short = false): array
    {
        $result = [
            "{$exception->getFile()}: {$exception->getLine()}",
            $exception->getTraceAsString(),
        ];

        if (!$short) {
            array_unshift($result, "{$exception->getMessage()}. Code: {$exception->getCode()}");
        }

        return $result;
    }
}
