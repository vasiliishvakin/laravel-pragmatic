<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping\Transform;

use InvalidArgumentException;
use Pragmatic\Data\Contracts\Mapping\MapperTransformContract;
use Pragmatic\Data\Data;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

final class DataDtoTransform implements MapperTransformContract
{
    public function __construct(
        private readonly ?string $dtoClass = null,
    ) {}

    public function resolve(ReflectionParameterData $parameter, Value $value): Value
    {
        $target = $this->dtoClass ?? $parameter->type->type;

        throw_unless(class_exists($target), InvalidArgumentException::class, 'Provided DTO class does not exist.');
        throw_unless(is_subclass_of($target, Data::class), InvalidArgumentException::class, 'Provided class must extend '.Data::class);

        if (! $value->exists()) {
            return Value::none();
        }

        $raw = $value->get();

        // If already instance of DTO — return as-is
        if (is_object($raw) && $raw instanceof $target) {
            return Value::some($raw);
        }

        // Delegate to StaticCallTransform which implements the generic static call behaviour.
        $transform = new StaticCallTransform($target, 'from');

        return $transform->resolve($parameter, Value::some($raw));
    }
}
