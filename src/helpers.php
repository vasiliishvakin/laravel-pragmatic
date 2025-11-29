<?php

declare(strict_types=1);

use Pragmatic\Debug\DebugManager;
use Pragmatic\Json\JsonManager;

if (! function_exists('debug')) {
    function debug(): DebugManager
    {
        return app(DebugManager::class);
    }
}

if (! function_exists('fast_hash')) {
    function fast_hash(string $value): string
    {
        return Hash::driver('fast')->make($value);
    }
}

if (! function_exists('json')) {
    function json(?string $driver = null): JsonManager
    {
        return $driver === null ? app(JsonManager::class) : app(JsonManager::class)->driver($driver);
    }
}

if (! function_exists('array_get_many')) {
    /**
     * Extract multiple values from array by name or position.
     * Throws exception if array has mixed key types in invalid order.
     */
    function array_get_many(array $array, array $keys): array
    {
        $values = [];
        $hasStringKeys = false;
        $hasNumericKeys = false;

        foreach (array_keys($array) as $k) {
            if (is_string($k)) {
                $hasStringKeys = true;
            } elseif (is_int($k)) {
                $hasNumericKeys = true;
            }
        }

        // basic consistency check
        if ($hasStringKeys && $hasNumericKeys) {
            // check if numeric keys appear *before* string keys
            $keysOrder = array_keys($array);
            $firstString = array_search(true, array_map('is_string', $keysOrder), true);
            $lastNumeric = array_search(true, array_reverse(array_map('is_int', $keysOrder)), true);

            if ($firstString !== false && $lastNumeric !== false && $lastNumeric < count($keysOrder) - $firstString - 1) {
                throw new InvalidArgumentException('Invalid mixed array: numeric and string keys interleaved.');
            }
        }

        foreach ($keys as $pos => $key) {
            if (array_key_exists($key, $array)) {
                $values[$key] = $array[$key];
            } elseif (array_key_exists($pos, $array)) {
                $values[$key] = $array[$pos];
            }
        }

        return $values;
    }
}
