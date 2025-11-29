<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping\Field;

use Illuminate\Support\Str;
use Pragmatic\Data\Contracts\Mapping\MapperFieldContract;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\DataAccessor;
use Pragmatic\Support\Value;

final class SnakeToCamel implements MapperFieldContract
{
    public function __construct(
        private readonly DataAccessor $dataAccessor
    ) {}

    public function resolve(ReflectionParameterData $parameter, mixed $data): Value
    {
        $snake = Str::snake($parameter->name);
        if (array_key_exists($snake, $data)) {
            return Value::some($data[$snake]);
        }

        return Value::none();
    }
}
