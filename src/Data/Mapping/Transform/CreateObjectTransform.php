<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping\Transform;

use InvalidArgumentException;
use Pragmatic\Data\Contracts\Mapping\MapperTransformContract;
use Pragmatic\Data\DataFactoryService;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

final class CreateObjectTransform implements MapperTransformContract
{
    public function __construct(
        private readonly DataFactoryService $factory,
        private readonly ?string $class = null,
    ) {}

    public function resolve(ReflectionParameterData $parameter, Value $value): Value
    {
        $target = $this->class ?? $parameter->type->type;

        throw_unless(class_exists($target), InvalidArgumentException::class, 'Provided class does not exist.');

        // Nothing to build
        if (! $value->exists()) {
            return Value::none();
        }

        $raw = $value->get();

        // If already an instance — return as-is when matches target
        if (is_object($raw) && $raw instanceof $target) {
            return Value::some($raw);
        }

        // If an array given — use DataFactoryService to construct with mapping support
        if (is_array($raw)) {
            $instance = $this->factory->make($target, $raw);

            return Value::some($instance);
        }

        // Fallback — pass the raw value as single constructor argument
        return Value::some(new $target($raw));
    }
}
