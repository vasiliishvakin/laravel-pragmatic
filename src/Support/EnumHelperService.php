<?php

declare(strict_types=1);

namespace Pragmatic\Support;

use BackedEnum;
use InvalidArgumentException;
use UnitEnum;

/**
 * Utility to inspect PHP enums (Backed and Unit enums) without using traits.
 *
 * Example:
 * $helper = new EnumHelper(MyEnum::class);
 * $helper->exists(1);
 */
final class EnumHelperService
{
    /**
     * Check whether the given backed value or name exists on the enum.
     *
     * @param  UnitEnum|string  $enum  Class-string of enum or enum instance
     */
    public function exists(UnitEnum|string $enum, string|int $value): bool
    {
        $class = $this->resolveEnumClass($enum);

        if (is_subclass_of($class, BackedEnum::class)) {
            /** @var class-string<BackedEnum> $c */
            $c = $class;

            return $c::tryFrom($value) !== null;
        }

        foreach ($class::cases() as $case) {
            if ($case->name === (string) $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return scalar values for backed enums, or names for unit enums.
     *
     * @param  UnitEnum|string  $enum  Class-string of enum or enum instance
     * @return array<int|string>
     */
    public function values(UnitEnum|string $enum): array
    {
        $class = $this->resolveEnumClass($enum);

        if (is_subclass_of($class, BackedEnum::class)) {
            return array_map(fn (BackedEnum $case) => $case->value, $class::cases());
        }

        return $this->names($class);
    }

    /**
     * Return enum case names.
     *
     * @param  UnitEnum|string  $enum  Class-string of enum or enum instance
     * @return string[]
     */
    public function names(UnitEnum|string $enum): array
    {
        $class = $this->resolveEnumClass($enum);

        return array_map(fn (UnitEnum $case) => $case->name, $class::cases());
    }

    /**
     * Return the enum case for the given value (or return the same instance if already provided).
     * Returns null when no matching case found or when an enum instance for a different enum class is passed.
     *
     * @param  UnitEnum|string  $enum  Class-string of enum or enum instance
     * @return UnitEnum|null
     */
    public function fromValue(UnitEnum|string $enum, UnitEnum|string|int $value): UnitEnum|BackedEnum|null
    {
        $class = $this->resolveEnumClass($enum);

        if ($value instanceof UnitEnum) {
            return $value::class === $class ? $value : null;
        }

        if (is_subclass_of($class, BackedEnum::class)) {
            /** @var class-string<BackedEnum> $c */
            $c = $class;

            return $c::tryFrom($value);
        }

        foreach ($class::cases() as $case) {
            if ($case->name === (string) $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Resolve a string or enum instance to an enum class name.
     *
     * @return class-string<UnitEnum>
     */
    private function resolveEnumClass(UnitEnum|string $enum): string
    {
        $class = \is_string($enum) ? $enum : $enum::class;

        if (! is_a($class, UnitEnum::class, true)) {
            throw new InvalidArgumentException("{$class} is not an enum");
        }

        /** @var class-string<UnitEnum> $class */
        return $class;
    }
}
