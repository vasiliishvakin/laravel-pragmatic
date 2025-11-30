<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Operation;

/**
 * Database transaction middleware for CQRS commands.
 *
 * Wraps command execution in a database transaction.
 * Automatically commits on success and rolls back on exceptions.
 *
 * Usage:
 * ```php
 * // Global (config) - wraps all commands in transaction
 * 'command_middleware' => [TransactionMiddleware::class],
 *
 * // Per-class - only for specific commands
 * public function middleware(): array
 * {
 *     return [TransactionMiddleware::class];
 * }
 *
 * // Runtime - for specific command instance
 * CommandBus::execute(
 *     CreateOrderCommand::make(...)->withMiddleware([TransactionMiddleware::class])
 * );
 * ```
 */
final class TransactionMiddleware implements Middleware
{
    /**
     * Handle the operation execution within a database transaction.
     *
     * @throws \Throwable
     */
    public function handle(Operation $operation, Closure $next): mixed
    {
        return DB::transaction(function () use ($operation, $next) {
            return $next($operation);
        });
    }
}
