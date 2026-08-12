<?php

namespace App\Services\Logging;

use App\Enums\LogType;
use App\Exceptions\BadMethodCallException;
use App\Models\ActivityLog;
use App\Models\Download;
use App\Models\Season;
use App\Models\Series;
use Error;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @method void debug(string $message, Exception|Error|array $context = [])
 * @method void info(string $message, Exception|Error|array $context = [])
 * @method void warning(string $message, Exception|Error|array $context = [])
 * @method void error(string $message, Exception|Error|array $context = [])
 */
final class AniarrLogger
{
    private ?int $seriesId = null;

    private ?int $seasonId = null;

    private ?int $downloadId = null;

    private ?string $source = null;

    /**
     * Возвращает отдельный logger со scope сериала.
     *
     * Используем clone, чтобы контекст singleton-сервиса не протекал между queue jobs.
     */
    public function forSeries(Series|int $series): self
    {
        $logger = clone $this;
        $logger->seriesId = $series instanceof Series ? $series->id : $series;
        $logger->seasonId = null;
        $logger->downloadId = null;

        return $logger;
    }

    /**
     * Возвращает отдельный logger со scope сезона и его сериала.
     */
    public function forSeason(Season|int $season): self
    {
        $logger = clone $this;
        $model = $season instanceof Season
            ? $season
            : Season::query()->find($season);

        $logger->seasonId = $model?->id;
        $logger->seriesId = $model?->series_id;
        $logger->downloadId = null;

        return $logger;
    }

    /**
     * Возвращает отдельный logger со scope Download → Season → Series.
     */
    public function forDownload(Download|int $download): self
    {
        $logger = clone $this;
        $model = $download instanceof Download
            ? $download
            : Download::query()->with('season')->find($download);

        if ($model === null) {
            $logger->seriesId = null;
            $logger->seasonId = null;
            $logger->downloadId = null;

            return $logger;
        }

        $model->loadMissing('season');

        $logger->downloadId = $model->id;
        $logger->seasonId = $model->season_id;
        $logger->seriesId = $model->season?->series_id;

        return $logger;
    }

    public function withSource(string $source): self
    {
        $logger = clone $this;
        $logger->source = $source;

        return $logger;
    }

    /**
     * Старый mutable API оставлен для совместимости с ещё не перенесённым кодом.
     */
    public function setSeries(Series|int $id): void
    {
        $this->seriesId = $id instanceof Series ? $id->id : $id;
        $this->seasonId = null;
        $this->downloadId = null;
    }

    public function resetSeries(): void
    {
        $this->seriesId = null;
        $this->seasonId = null;
        $this->downloadId = null;
        $this->source = null;
    }

    public function __call(string $name, array $arguments): void
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
     * Структурированное пользовательское событие.
     */
    public function event(
        string $event,
        string $message,
        LogType $type = LogType::INFO,
        Exception|Error|array $context = [],
    ): void {
        $this->write($type, $message, $context, $event);
    }

    /**
     * Записать техническое сообщение и, при наличии scope, ActivityLog.
     */
    public function log(LogType $level, string $message, Exception|Error|array $context = []): void
    {
        $this->write($level, $message, $context);
    }

    /**
     * Записать ошибку исключения.
     */
    public function exception(Exception|Error $exception, ?string $url = null, ?string $event = null): void
    {
        $context = [];

        if ($url) {
            $context['url'] = $url;
        }

        $context['exception'] = implode("\n", $this->exceptionToContext($exception, short: true));

        $message = "Code: {$exception->getCode()}, {$exception->getMessage()}";

        if ($event !== null) {
            $this->event($event, $message, LogType::ERROR, $context);

            return;
        }

        $this->error($message, $context);
    }

    private function write(
        LogType $level,
        string $message,
        Exception|Error|array $context,
        ?string $event = null,
    ): void {
        $normalizedContext = $this->normalizeContext($context);

        if ($level !== LogType::DEBUG && $this->hasActivityScope()) {
            ActivityLog::query()->create([
                'series_id' => $this->seriesId,
                'season_id' => $this->seasonId,
                'download_id' => $this->downloadId,
                'source' => $this->source,
                'event' => $event,
                'message' => Str::limit($message, 1000),
                'type' => $level,
                'context' => $normalizedContext === [] ? null : $normalizedContext,
            ]);
        }

        if ($level === LogType::DEBUG && app()->isProduction()) {
            return;
        }

        $logContext = array_filter([
            'series_id' => $this->seriesId,
            'season_id' => $this->seasonId,
            'download_id' => $this->downloadId,
            'source' => $this->source,
            'event' => $event,
            'context' => $normalizedContext === [] ? null : $normalizedContext,
        ], static fn (mixed $value): bool => $value !== null);

        Log::channel('aniarr')->log($level->value, $message, $logContext);
    }

    private function hasActivityScope(): bool
    {
        return $this->seriesId !== null
            || $this->seasonId !== null
            || $this->downloadId !== null;
    }

    /**
     * @return array<string|int, mixed>
     */
    private function normalizeContext(Exception|Error|array $context): array
    {
        if (is_array($context)) {
            return $context;
        }

        return [
            'exception' => implode("\n", $this->exceptionToContext($context)),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function exceptionToContext(Exception|Error $exception, bool $short = false): array
    {
        $result = [
            "{$exception->getFile()}: {$exception->getLine()}",
            $exception->getTraceAsString(),
        ];

        if (! $short) {
            array_unshift($result, "{$exception->getMessage()}. Code: {$exception->getCode()}");
        }

        return $result;
    }
}
