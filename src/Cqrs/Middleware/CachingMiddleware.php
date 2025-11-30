<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Operation;
use Pragmatic\Cqrs\Query;

/**
 * Caching middleware for CQRS queries.
 *
 * Caches query results to improve performance.
 * Only works with queries (not commands).
 *
 * To use this middleware, implement a cacheKey() method on your Query:
 *
 * ```php
 * class GetUserQuery extends Query
 * {
 *     public function __construct(
 *         private readonly int $userId,
 *     ) {}
 *
 *     public function middleware(): array
 *     {
 *         return [CachingMiddleware::class];
 *     }
 *
 *     // Required: provide cache key
 *     public function cacheKey(): string
 *     {
 *         return "user:{$this->userId}";
 *     }
 *
 *     // Optional: specify TTL (default: 3600 seconds)
 *     public function cacheTtl(): int
 *     {
 *         return 7200; // 2 hours
 *     }
 *
 *     public function execute(): User
 *     {
 *         return User::find($this->userId);
 *     }
 * }
 * ```
 */
final class CachingMiddleware implements Middleware
{
    /**
     * Default cache TTL in seconds (1 hour).
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Create a new caching middleware instance.
     *
     * @param  CacheRepository  $cache  Injected from container
     */
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    /**
     * Handle the operation execution with caching.
     */
    public function handle(Operation $operation, Closure $next): mixed
    {
        // Only cache queries, not commands
        if ($operation instanceof Command) {
            return $next($operation);
        }

        // If the query doesn't have a cacheKey() method, skip caching
        if (! method_exists($operation, 'cacheKey')) {
            return $next($operation);
        }

        $cacheKey = $operation->cacheKey();
        $cacheTtl = method_exists($operation, 'cacheTtl')
            ? $operation->cacheTtl()
            : self::DEFAULT_TTL;

        // Try to get from cache, or execute and cache the result
        return $this->cache->remember(
            $cacheKey,
            $cacheTtl,
            fn () => $next($operation)
        );
    }
}
