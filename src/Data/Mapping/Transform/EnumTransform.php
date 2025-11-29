<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping\Transform;

use BackedEnum;
use InvalidArgumentException;
use Pragmatic\Data\Contracts\Mapping\MapperTransformContract;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

final class EnumTransform implements MapperTransformContract
{
    public function __construct(
        private readonly ?string $enumClass = null
    ) {}

    public function resolve(ReflectionParameterData $parameter, Value $value): Value
    {
        throw_unless($parameter->type->isEnum, InvalidArgumentException::class, 'Parameter type must be an enum.');
        $enumClass = $this->enumClass ?? $parameter->type->type;
        throw_unless(is_subclass_of($enumClass, BackedEnum::class), InvalidArgumentException::class, 'Provided enum class must be a backed enum.');
        $enumInstance = $enumClass::tryFrom($value->get());

        return Value::maybe($enumInstance);
    }
}
