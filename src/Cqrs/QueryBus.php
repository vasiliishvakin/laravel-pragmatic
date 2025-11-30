<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

/**
 * QueryBus executes queries with automatic dependency injection and middleware support.
 *
 * Middleware execution order:
 * 1. Global middleware (from config)
 * 2. Per-class middleware (from query->middleware())
 * 3. Runtime middleware (from query->withMiddleware())
 *
 * Usage:
 * ```php
 * // Simple execution
 * $user = QueryBus::execute(GetUserQuery::make(userId: 1));
 *
 * // With runtime middleware
 * $user = QueryBus::execute(
 *     GetUserQuery::make(userId: 1)
 *         ->withMiddleware([CachingMiddleware::class])
 * );
 * ```
 */
final class QueryBus extends AbstractBus
{
    /**
     * Execute a query through middleware pipeline with automatic dependency injection.
     *
     * The execution process:
     * 1. Collect middleware from global config, per-class, and runtime sources
     * 2. Execute through Pipeline (middleware can inspect/modify/short-circuit)
     * 3. Call boot() method if not already booted
     * 4. Call execute() method with automatic parameter resolution
     *
     * @param  Query  $query  Query instance to execute
     * @return mixed Query result (may be transformed by middleware)
     */
    public function execute(Query $query): mixed
    {
        return $this->executeOperation($query, 'pragmatic.cqrs.query_middleware');
    }
}
