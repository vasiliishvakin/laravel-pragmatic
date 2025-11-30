<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

use Pragmatic\Cqrs\Concerns\Bootable;
use Pragmatic\Cqrs\Concerns\HasMiddleware;

/**
 * Base class for all queries in the CQRS pattern.
 *
 * Queries are read operations that fetch data from any source
 * (database, API, AI, cache, files, etc.).
 *
 * Supports two-phase initialization:
 * 1. Constructor - receives data parameters
 * 2. boot() - optional dependency injection phase (called automatically by QueryBus)
 *
 * Usage:
 * ```php
 * class GetUserQuery extends Query
 * {
 *     public function __construct(
 *         private readonly int $userId,
 *     ) {}
 *
 *     // Optional: inject dependencies before execute()
 *     public function boot(CacheService $cache): void
 *     {
 *         $this->cache = $cache;
 *     }
 *
 *     public function execute(UserRepository $repository): User
 *     {
 *         return $repository->find($this->userId);
 *     }
 * }
 *
 * // Execute via QueryBus
 * $user = QueryBus::execute(
 *     GetUserQuery::make(userId: 1)
 * );
 * ```
 */
abstract class Query
{
    use Bootable;
    use HasMiddleware;

    /**
     * Execute the query and return result.
     *
     * Must be implemented in child classes.
     * Dependencies are automatically injected via type-hints.
     */
    abstract public function execute(): mixed;

    /**
     * Factory method for fluent API construction.
     *
     * @param  mixed  ...$params  Constructor parameters
     */
    public static function make(mixed ...$params): static
    {
        return new static(...$params);
    }
}
