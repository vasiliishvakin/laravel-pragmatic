<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

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
abstract class Query extends Operation
{
    //
}
