<?php

declare(strict_types=1);

namespace Pragmatic\Cache\Concerns;

use Closure;
use DateTimeInterface;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class CacheMacros
{
    /**
     * Register cache macros for enhanced functionality.
     */
    public static function register(): void
    {
        /**
         * Remember a value with prepare/restore hooks for serialization/deserialization.
         *
         * This macro extends Laravel's standard remember() with transformation hooks:
         * - prepare: transforms data before storing in cache (e.g., $model->toArray())
         * - restore: transforms data after retrieving from cache (e.g., Model::hydrate())
         *
         * Useful for caching complex objects like Eloquent models with relationships.
         *
         * @param  string  $key  Cache key
         * @param  int|DateTimeInterface|Closure|null  $ttl  Time to live (null = forever)
         * @param  Closure  $callback  Callback to generate fresh data
         * @param  Closure|null  $prepare  Optional: transform data before caching
         * @param  Closure|null  $restore  Optional: transform data after retrieving
         * @return mixed The restored value (or fresh data on cache miss)
         *
         * @example
         * Cache::rememberAndRestore(
         *     key: 'user:1',
         *     ttl: 3600,
         *     callback: fn() => User::with('roles')->find(1),
         *     prepare: fn($user) => $user->toArray(),
         *     restore: fn($data) => User::hydrate([$data])->first()
         * );
         */
        Repository::macro('rememberAndRestore', function (
            string $key,
            int|DateTimeInterface|Closure|null $ttl,
            Closure $callback,
            ?Closure $prepare = null,
            ?Closure $restore = null
        ): mixed {
            /** @var CacheRepository $this */

            // Try to retrieve from cache
            $cachedValue = $this->get($key);

            // Cache hit - restore and return
            if ($cachedValue !== null) {
                return $restore !== null ? $restore($cachedValue) : $cachedValue;
            }

            // Cache miss - generate fresh data
            $freshData = $callback();

            // Prepare data for storage (if prepare callback provided)
            $dataToStore = $prepare !== null ? $prepare($freshData) : $freshData;

            // Resolve TTL (support Closure)
            $ttlValue = $ttl instanceof Closure ? $ttl() : $ttl;

            // Store in cache (forever if ttl is null)
            if ($ttlValue === null) {
                $this->forever($key, $dataToStore);
            } else {
                $this->put($key, $dataToStore, $ttlValue);
            }

            // Return fresh (non-transformed) data
            return $freshData;
        });
    }
}
