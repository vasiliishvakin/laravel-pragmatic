<?php

declare(strict_types=1);

namespace Pragmatic\Support;

use LogicException;

/**
 * @template T
 */
final class Value
{
    private readonly bool $present;

    private readonly mixed $value;

    private function __construct(bool $present, mixed $value = null)
    {
        $this->present = $present;
        $this->value = $value;
    }

    /**
     * Create a present value (can be null).
     *
     * @template V
     *
     * @param  V  $value
     * @return Value<V>
     */
    public static function some(mixed $value): self
    {
        return new self(true, $value);
    }

    /**
     * Create an absent value (missing).
     *
     * @return Value<never>
     */
    public static function none(): self
    {
        static $none;

        return $none ??= new self(false);
    }

    public static function maybe(mixed $condition, mixed $value = null): self
    {
        // If condition is callable — evaluate it
        if (is_callable($condition)) {
            $condition = $condition();
        }

        // If no explicit value given — treat condition itself as the value
        $value ??= $condition;

        // Convert condition to boolean
        $isTrue = (bool) $condition;

        if (! $isTrue) {
            return self::none();
        }

        // If the value is callable — resolve it
        if (is_callable($value)) {
            $value = $value();
        }

        // Wrap in Value if needed
        return $value instanceof self
            ? $value
            : self::some($value);
    }

    /** True if key/value exists (even if it's null). */
    public function exists(): bool
    {
        return $this->present;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function or(mixed $fallback): self
    {
        if ($this->present) {
            return $this;
        }

        if (is_callable($fallback)) {
            $fallback = $fallback();
        }

        return $fallback instanceof self
            ? $fallback
            : self::some($fallback);
    }

    /**
     * Map present value, keep none otherwise.
     *
     * @template R
     *
     * @param  callable(mixed):R  $fn
     * @return Value<R>
     */
    public function map(callable $fn): self
    {
        if (! $this->present) {
            return self::none();
        }

        return self::some($fn($this->value));
    }

    /**
     * Chain operation that receives and must return a Value.
     * Always returns a Value (the callback decides what kind).
     *
     * @param  callable(self): self  $callback
     */
    public function then(callable $callback): self
    {
        $result = $callback($this);

        throw_unless(
            $result instanceof self,
            LogicException::class,
            sprintf(
                'Callback for %s::then() must return instance of %s, got %s.',
                self::class,
                self::class,
                get_debug_type($result)
            )
        );

        return $result;
    }

    public function tap(callable $fn): self
    {
        if ($this->present) {
            $fn($this->value);
        }

        return $this;
    }
}
