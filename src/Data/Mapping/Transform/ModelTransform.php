<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping\Transform;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Pragmatic\Data\Contracts\Mapping\MapperTransformContract;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

final class ModelTransform implements MapperTransformContract
{
    public function __construct(
        private readonly ?string $class = null,
    ) {}

    public function resolve(ReflectionParameterData $parameter, Value $value): Value
    {
        $target = $this->class ?? $parameter->type->type;

        throw_unless(class_exists($target), InvalidArgumentException::class, 'Provided class does not exist.');
        throw_unless(is_subclass_of($target, Model::class), InvalidArgumentException::class, 'Provided class is not an Eloquent Model.');

        // Nothing to build
        if (! $value->exists()) {
            return Value::none();
        }

        $raw = $value->get();

        // If already an instance — return as-is when matches target
        if (is_object($raw) && $raw instanceof $target) {
            return Value::some($raw);
        }

        $model = $target::find($raw);

        return Value::maybe($model);
    }
}
