<?php

declare(strict_types=1);

namespace Pragmatic\Cache\Concerns;

trait MakeCacheKey
{
    abstract public function cacheUtils(): CacheUtils;

    public function makeKey(string|array ...$parts): string
    {
        $prefix = $this->cachePrefix ?? null;
        if ($prefix !== null) {
            array_unshift($parts, $prefix);
        }

        if (count($parts) === 1 && is_string($parts[0])) {
            return $parts[0];
        }

        return $this->cacheUtils()->buildCacheKey(...$parts);
    }
}
