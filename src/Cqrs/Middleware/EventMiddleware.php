<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Middleware;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Events\CommandExecuted;
use Pragmatic\Cqrs\Events\CommandExecuting;
use Pragmatic\Cqrs\Events\CommandFailed;
use Pragmatic\Cqrs\Events\QueryExecuted;
use Pragmatic\Cqrs\Events\QueryExecuting;
use Pragmatic\Cqrs\Events\QueryFailed;
use Pragmatic\Cqrs\Query;

/**
 * Event middleware for CQRS lifecycle events.
 *
 * Dispatches events at different stages of Query/Command execution:
 * - Before execution: QueryExecuting / CommandExecuting
 * - After successful execution: QueryExecuted / CommandExecuted
 * - On failure: QueryFailed / CommandFailed
 *
 * Usage:
 * ```php
 * // Global (config) - all operations will dispatch events
 * 'query_middleware' => [EventMiddleware::class],
 * 'command_middleware' => [EventMiddleware::class],
 *
 * // Listen to events
 * Event::listen(QueryExecuting::class, function (QueryExecuting $event) {
 *     Log::info('Query starting', ['query' => get_class($event->query)]);
 * });
 *
 * Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
 *     Log::info('Query completed', [
 *         'query' => get_class($event->query),
 *         'time' => $event->executionTime,
 *     ]);
 * });
 * ```
 */
final class EventMiddleware implements Middleware
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function handle(Query|Command $operation, Closure $next): mixed
    {
        $startTime = microtime(true);

        // Dispatch "executing" event
        $this->dispatchExecutingEvent($operation);

        try {
            $result = $next($operation);

            // Dispatch "executed" event
            $executionTime = microtime(true) - $startTime;
            $this->dispatchExecutedEvent($operation, $result, $executionTime);

            return $result;
        } catch (\Throwable $e) {
            // Dispatch "failed" event
            $executionTime = microtime(true) - $startTime;
            $this->dispatchFailedEvent($operation, $e, $executionTime);

            throw $e;
        }
    }

    private function dispatchExecutingEvent(Query|Command $operation): void
    {
        if ($operation instanceof Query) {
            $this->events->dispatch(new QueryExecuting($operation));
        } else {
            $this->events->dispatch(new CommandExecuting($operation));
        }
    }

    private function dispatchExecutedEvent(Query|Command $operation, mixed $result, float $executionTime): void
    {
        if ($operation instanceof Query) {
            $this->events->dispatch(new QueryExecuted($operation, $result, $executionTime));
        } else {
            $this->events->dispatch(new CommandExecuted($operation, $result, $executionTime));
        }
    }

    private function dispatchFailedEvent(Query|Command $operation, \Throwable $exception, float $executionTime): void
    {
        if ($operation instanceof Query) {
            $this->events->dispatch(new QueryFailed($operation, $exception, $executionTime));
        } else {
            $this->events->dispatch(new CommandFailed($operation, $exception, $executionTime));
        }
    }
}
