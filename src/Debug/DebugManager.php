<?php

declare(strict_types=1);

namespace Pragmatic\Debug;

use BadMethodCallException;
use InvalidArgumentException;
use Laravel\Pennant\Feature;
use Pragmatic\Debug\Contracts\DebugDriver;
use Pragmatic\Debug\Contracts\DebugManagerInstance;
use Pragmatic\Debug\Drivers\NullDriver;

final class DebugManager implements DebugDriver, DebugManagerInstance
{
    private static ?bool $enabled = null;

    public function __construct(
        private readonly DebugFactoryContainer $factoryContainer,
        private readonly ?DebugDriver $driver = null,
    ) {}

    /**
     * Magic method to forward calls to the driver.
     */
    public function __call(string $method, array $args): mixed
    {
        $driver = $this->instance()->rawDriver();

        if (! method_exists($driver, $method)) {
            throw new BadMethodCallException(
                "Method {$method} does not exist on driver ".get_class($driver)
            );
        }

        return $driver->$method(...$args);
    }

    /**
     * Get a debug driver instance.
     */
    public function driver(?string $name = null): static
    {
        $name ??= config('debug.default');

        return $this->factoryContainer->get($name);
    }

    /**
     * Get the raw debug driver.
     */
    public function rawDriver(): DebugDriver
    {
        if ($this->driver === null) {
            throw new InvalidArgumentException('No debug driver has been set.');
        }

        // Return NullDriver if debugging is disabled
        if (! $this->isEnabled()) {
            return new NullDriver;
        }

        return $this->driver;
    }

    /**
     * Check if a driver has been set.
     */
    public function hasDriver(): bool
    {
        return $this->driver !== null;
    }

    /**
     * Get the current instance or default driver.
     */
    public function instance(): static
    {
        return $this->hasDriver() ? $this : $this->driver();
    }

    /**
     * Check if debugging is enabled.
     */
    public function isEnabled(): bool
    {
        // Use cached result if available
        if (self::$enabled !== null) {
            return self::$enabled;
        }

        // Check Pennant feature flag first (if available)
        if (class_exists(Feature::class)) {
            $featureName = config('debug.pennant_feature', 'force-debug');

            try {
                if (Feature::active($featureName)) {
                    return self::$enabled = true;
                }
            } catch (\Throwable) {
                // Feature check failed, continue to config check
            }
        }

        // Fall back to configuration
        return self::$enabled = (bool) config('debug.enabled', false);
    }

    /**
     * Enable debugging (sets runtime flag).
     */
    public function enable(): void
    {
        self::$enabled = true;
    }

    /**
     * Disable debugging (sets runtime flag).
     */
    public function disable(): void
    {
        self::$enabled = false;
    }

    /**
     * Reset cached enabled state.
     */
    public function resetState(): void
    {
        self::$enabled = null;
    }

    /**
     * Shorthand for core driver.
     */
    public function core(): static
    {
        return $this->driver('core');
    }

    /**
     * Shorthand for laradumps driver.
     */
    public function ds(): static
    {
        return $this->driver('laradumps');
    }

    /**
     * Shorthand for log driver.
     */
    public function log(?string $level = null): static
    {
        $driver = $this->driver('log');

        if ($level !== null && method_exists($driver->rawDriver(), 'level')) {
            $driver->rawDriver()->level($level);
        }

        return $driver;
    }

    /**
     * Dump variable(s) and continue execution.
     */
    public function dump(mixed ...$vars): static
    {
        $this->instance()->rawDriver()->dump(...$vars);

        return $this;
    }

    /**
     * Dump variable(s) and die (stop execution).
     */
    public function dd(mixed ...$vars): never
    {
        $this->instance()->rawDriver()->dd(...$vars);
    }

    /**
     * Stop execution without dumping.
     */
    public function die(string $message = ''): never
    {
        $this->instance()->rawDriver()->die($message);
    }
}
