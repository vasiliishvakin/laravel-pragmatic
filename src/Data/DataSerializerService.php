<?php

declare(strict_types=1);

namespace Pragmatic\Data;

use Illuminate\Support\Collection;
use Pragmatic\Support\ReflectionReader;

/**
 * Responsible for serializing DTO objects back into arrays.
 * Mirrors the behavior of DataFactoryService::make().
 */
final class DataSerializerService
{
    public function __construct(
        private readonly ReflectionReader $reflection,
    ) {}

    /**
     * Convert DTO object to plain array based on constructor parameters.
     */
    public function toArray(object $dto): array
    {
        $params = $this->reflection->constructorParams($dto::class, true);

        return collect($params)
            ->pluck('name')
            ->mapWithKeys(fn (string $name) => [
                $name => $this->normalizeValueToArray($dto->{$name}),
            ])
            ->all();
    }

    /**
     * Normalize any value recursively to array/scalar.
     */
    protected function normalizeValueToArray(mixed $value): mixed
    {
        // If value has its own toArray() method — respect it
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        // Handle Laravel Collection
        if ($value instanceof Collection) {
            return $value
                ->map(fn ($item) => $this->normalizeValueToArray($item))
                ->all();
        }

        // Handle array of items
        if (is_array($value)) {
            return array_map(fn ($v) => $this->normalizeValueToArray($v), $value);
        }

        // Fallback — scalar or simple object
        return $value;
    }
}
