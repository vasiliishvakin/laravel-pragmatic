<?php

declare(strict_types=1);

namespace Pragmatic\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Universal accessor for various data sources.
 *
 * Supports: array, Collection, Model, Request, object
 * Returns: Value wrapper to indicate presence or absence.
 */
final class DataAccessor
{
    /**
     * Try to extract a value as Value-object from any supported data source.
     */
    public function get(mixed $source, string $key): Value
    {
        return match (true) {
            is_array($source) => $this->extractFromArray($source, $key),
            $source instanceof Request => $this->extractFromRequest($source, $key),
            $source instanceof Model => $this->extractFromModel($source, $key),
            $source instanceof Collection => $this->extractFromCollection($source, $key),
            is_object($source) => $this->extractFromObject($source, $key),
            default => Value::none(),
        };
    }

    /**
     * Set value to any supported target.
     */
    public function set(mixed &$target, string $key, mixed $value): void
    {
        match (true) {
            is_array($target) => Arr::set($target, $key, $value),
            $target instanceof Model => $target->setAttribute($key, $value),
            $target instanceof Collection => $target->put($key, $value),
            is_object($target) => $target->{$key} = $value,
            default => throw new InvalidArgumentException(
                sprintf('Unsupported data type for setting value: %s', get_debug_type($target))
            ),
        };
    }

    /**
     * Convert source to plain array when possible.
     */
    public function all(mixed $source): array
    {
        return match (true) {
            is_array($source) => $source,
            $source instanceof Request => $source->all(),
            $source instanceof Model => $source->getAttributes(),
            $source instanceof Collection => $source->toArray(),
            is_object($source) => get_object_vars($source),
            default => [],
        };
    }

    private function extractFromArray(array $source, string $key): Value
    {
        return Arr::has($source, $key)
            ? Value::some(Arr::get($source, $key))
            : Value::none();
    }

    private function extractFromRequest(Request $source, string $key): Value
    {
        return $source->has($key)
            ? Value::some($source->input($key))
            : Value::none();
    }

    private function extractFromModel(Model $source, string $key): Value
    {
        if ($source->offsetExists($key) || $source->hasGetMutator($key) || $source->isRelation($key)) {
            return Value::some($source->getAttribute($key));
        }

        return Value::none();
    }

    private function extractFromCollection(Collection $source, string $key): Value
    {
        return $source->has($key)
            ? Value::some($source->get($key))
            : Value::none();
    }

    private function extractFromObject(object $source, string $key): Value
    {
        if (property_exists($source, $key)) {
            return Value::some($source->{$key});
        }

        $getter = 'get'.ucfirst($key);
        if (method_exists($source, $getter)) {
            return Value::some($source->{$getter}());
        }

        return Value::none();
    }
}
