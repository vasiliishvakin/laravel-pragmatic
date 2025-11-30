<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

/**
 * CommandBus executes commands with automatic dependency injection and middleware support.
 *
 * Middleware execution order:
 * 1. Global middleware (from config)
 * 2. Per-class middleware (from command->middleware())
 * 3. Runtime middleware (from command->withMiddleware())
 *
 * Usage:
 * ```php
 * // Simple execution
 * $order = CommandBus::execute(
 *     CreateOrderCommand::make(userId: 1, items: [...])
 * );
 *
 * // With runtime middleware
 * $order = CommandBus::execute(
 *     CreateOrderCommand::make(userId: 1, items: [...])
 *         ->withMiddleware([TransactionMiddleware::class])
 * );
 * ```
 */
final class CommandBus extends AbstractBus
{
    /**
     * Execute a command through middleware pipeline with automatic dependency injection.
     *
     * The execution process:
     * 1. Collect middleware from global config, per-class, and runtime sources
     * 2. Execute through Pipeline (middleware can inspect/modify/short-circuit)
     * 3. Call boot() method if not already booted
     * 4. Call execute() method with automatic parameter resolution
     *
     * @param  Command  $operation  Command instance to execute
     * @return mixed Command result (optional, may be transformed by middleware)
     */
    public function execute(Command $operation): mixed
    {
        return $this->executeOperation($operation, 'pragmatic.cqrs.command_middleware');
    }
}
