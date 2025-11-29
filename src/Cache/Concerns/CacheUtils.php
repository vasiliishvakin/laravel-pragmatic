<?php

declare(strict_types=1);

namespace Pragmatic\Cache\Concerns;

final class CacheUtils
{
    public function __construct(
        protected readonly string $delimiter,
    ) {}

    public function buildCacheKey(...$parts): string
    {
        $flat = [];

        foreach ($parts as $part) {
            if (is_array($part)) {
                array_push($flat, ...$this->flattenParts($part));
            } else {
                $flat[] = $part;
            }
        }

        return implode($this->delimiter, $flat);
    }

    protected function flattenParts(array $parts): array
    {
        $result = [];
        array_walk_recursive($parts, static function ($value) use (&$result) {
            $result[] = $value;
        });

        return $result;
    }
}
