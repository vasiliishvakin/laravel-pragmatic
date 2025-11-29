<?php

declare(strict_types=1);

namespace Pragmatic\Data;

use Pragmatic\Facades\Reflection;

trait ToArray
{
    public function toArray(): array
    {
        $params = Reflection::constructorParams(static::class);

        return $params
            ->pluck('name')
            ->mapWithKeys(fn (string $name) => [$name => $this->normalizeValue($this->{$name})])
            ->all();
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->normalizeValue($v), $value);
        }

        return $value;
    }
}
