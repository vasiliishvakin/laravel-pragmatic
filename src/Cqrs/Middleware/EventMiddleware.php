<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Middleware;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Pragmatic\Cqrs\Action;
use Pragmatic\Cqrs\BaseOperation;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Events\ActionExecuted;
use Pragmatic\Cqrs\Events\ActionExecuting;
use Pragmatic\Cqrs\Events\ActionFailed;
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
 * Dispatches events at different stages of Query/Command/Action execution:
 * - Before execution: QueryExecuting / CommandExecuting / ActionExecuting
 * - After successful execution: QueryExecuted / CommandExecuted / ActionExecuted
 * - On failure: QueryFailed / CommandFailed / ActionFailed
 *
 * Usage:
 * ```php
 * // Global (config) - all operations will dispatch events
 * 'query_middleware' => [EventMiddleware::class],
 * 'command_middleware' => [EventMiddleware::class],
 * 'action_middleware' => [EventMiddleware::class],
 *
 * // Listen to events
 * Event::listen(QueryExecuting::class, function (QueryExecuting $event) {
 *     Log::info('Query starting', ['query' => get_class($event->query)]);
 * });
 *
 * Event::listen(ActionExecuted::class, function (ActionExecuted $event) {
 *     Log::info('Action completed', [
 *         'action' => get_class($event->action),
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

    public function handle(BaseOperation $operation, Closure $next): mixed
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

    private function dispatchExecutingEvent(BaseOperation $operation): void
    {
        if ($operation instanceof Query) {
            $this->events->dispatch(new QueryExecuting($operation));
        } elseif ($operation instanceof Command) {
            $this->events->dispatch(new CommandExecuting($operation));
        } elseif ($operation instanceof Action) {
            $this->events->dispatch(new ActionExecuting($operation));
        }
    }

    private function dispatchExecutedEvent(BaseOperation $operation, mixed $result, float $executionTime): void
    {
        if ($operation instanceof Query) {
            $this->events->dispatch(new QueryExecuted($operation, $result, $executionTime));
        } elseif ($operation instanceof Command) {
            $this->events->dispatch(new CommandExecuted($operation, $result, $executionTime));
        } elseif ($operation instanceof Action) {
            $this->events->dispatch(new ActionExecuted($operation, $result, $executionTime));
        }
    }

    private function dispatchFailedEvent(BaseOperation $operation, \Throwable $exception, float $executionTime): void
    {
        if ($operation instanceof Query) {
            $this->events->dispatch(new QueryFailed($operation, $exception, $executionTime));
        } elseif ($operation instanceof Command) {
            $this->events->dispatch(new CommandFailed($operation, $exception, $executionTime));
        } elseif ($operation instanceof Action) {
            $this->events->dispatch(new ActionFailed($operation, $exception, $executionTime));
        }
    }
}
