<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping\Transform;

use InvalidArgumentException;
use Pragmatic\Data\Contracts\Mapping\MapperTransformContract;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

/**
 * Transform that builds an object by calling a static method on a class.
 *
 * By default it will call `from($value)` but you can provide a different
 * method name and extra arguments via constructor params.
 */
final class StaticCallTransform implements MapperTransformContract
{
    /**
     * @param  string|null  $class  Fully-qualified class name to call the method on. If null — parameter type is used.
     * @param  string  $method  Static method name to call. Defaults to 'from'.
     * @param  array<int,mixed>  $extraArgs  Additional arguments appended after the value when calling the method.
     */
    public function __construct(
        private readonly ?string $class = null,
        private readonly string $method = 'from',
        private readonly array $extraArgs = [],
    ) {}

    public function resolve(ReflectionParameterData $parameter, Value $value): Value
    {
        $target = $this->class ?? $parameter->type->type;

        throw_unless(class_exists($target), InvalidArgumentException::class, 'Provided class does not exist.');

        if (! $value->exists()) {
            return Value::none();
        }

        $raw = $value->get();

        // If already instance of target — return as-is
        if (is_object($raw) && $raw instanceof $target) {
            return Value::some($raw);
        }

        // Validate method exists and is callable statically
        if (! is_callable([$target, $this->method])) {
            throw new InvalidArgumentException(sprintf('Static method %s::%s is not callable.', $target, $this->method));
        }

        // Call static method. We provide the raw value as the first arg, then any extra args.
        $args = array_merge([$raw], $this->extraArgs);

        $instance = $target::{$this->method}(...$args);

        return Value::maybe($instance);
    }
}
