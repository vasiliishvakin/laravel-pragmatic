<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping\Transform;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Pragmatic\Data\Contracts\Mapping\MapperTransformContract;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

final class ResolveFromContainerTransform implements MapperTransformContract
{
    public function __construct(
        private readonly Container $container,
        private readonly ?string $class = null,
        private readonly array $makeParams = [],
    ) {}

    public function resolve(ReflectionParameterData $parameter, Value $value): Value
    {
        $target = $this->class ?? $parameter->type->type;

        throw_unless(class_exists($target) || interface_exists($target), InvalidArgumentException::class, 'Provided class/interface does not exist.');

        // If value exists and is an array — pass it as parameters to container make
        if ($value->exists()) {
            $raw = $value->get();

            if (is_array($raw)) {
                $instance = $this->container->make($target, array_merge($this->makeParams, $raw));

                return Value::some($instance);
            }

            // if value is scalar or object — try to resolve using provided params (no sensible mapping)
            return Value::some($this->container->make($target, $this->makeParams));
        }

        // No explicit value — don't create implicitly, return none
        return Value::none();
    }
}
