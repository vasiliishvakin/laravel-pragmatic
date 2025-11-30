<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Contracts;

use Closure;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\Query;

/**
 * Middleware contract for CQRS operations.
 *
 * Middleware can intercept Query/Command execution to:
 * - Perform actions before execution (validation, logging, etc.)
 * - Perform actions after execution (caching, logging, etc.)
 * - Transform the result
 * - Short-circuit execution (return early without calling $next)
 * - Wrap execution (transactions, error handling, etc.)
 *
 * Usage example:
 * ```php
 * class LoggingMiddleware implements Middleware
 * {
 *     public function __construct(
 *         private LoggerInterface $logger
 *     ) {}
 *
 *     public function handle(Query|Command $operation, Closure $next): mixed
 *     {
 *         $this->logger->info('Executing: ' . get_class($operation));
 *
 *         $result = $next($operation);
 *
 *         $this->logger->info('Completed: ' . get_class($operation));
 *
 *         return $result;
 *     }
 * }
 * ```
 */
interface Middleware
{
    /**
     * Handle the operation execution.
     *
     * @param  Query|Command  $operation  The operation being executed
     * @param  Closure  $next  The next middleware in the pipeline
     * @return mixed The result of the operation (can be transformed)
     */
    public function handle(Query|Command $operation, Closure $next): mixed;
}
