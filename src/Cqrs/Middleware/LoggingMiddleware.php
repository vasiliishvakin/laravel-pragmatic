<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Middleware;

use Closure;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Operation;
use Pragmatic\Cqrs\Query;
use Psr\Log\LoggerInterface;

/**
 * Logging middleware for CQRS operations.
 *
 * Logs before and after execution of queries and commands.
 * Useful for debugging, auditing, and monitoring.
 *
 * Usage:
 * ```php
 * // Global (config)
 * 'query_middleware' => [LoggingMiddleware::class],
 *
 * // Per-class
 * public function middleware(): array
 * {
 *     return [LoggingMiddleware::class];
 * }
 *
 * // Runtime
 * QueryBus::execute(
 *     GetUserQuery::make(1)->withMiddleware([LoggingMiddleware::class])
 * );
 * ```
 */
final class LoggingMiddleware implements Middleware
{
    /**
     * Create a new logging middleware instance.
     *
     * @param  LoggerInterface  $logger  Injected from container
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the operation execution with logging.
     */
    public function handle(Operation $operation, Closure $next): mixed
    {
        $operationType = $operation instanceof Query ? 'Query' : 'Command';
        $operationClass = get_class($operation);

        $this->logger->info("Executing {$operationType}: {$operationClass}");

        try {
            $result = $next($operation);

            $this->logger->info("Completed {$operationType}: {$operationClass}");

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error("Failed {$operationType}: {$operationClass}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
