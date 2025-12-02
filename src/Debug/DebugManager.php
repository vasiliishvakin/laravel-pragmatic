<?php

declare(strict_types=1);

namespace Pragmatic\Debug;

use InvalidArgumentException;
use LaraDumps\LaraDumps\LaraDumps as LaravelLaraDumps;
use LaraDumps\LaraDumpsCore\LaraDumps;
use Laravel\Pennant\Feature;
use Pragmatic\Debug\Contracts\DebugDriver;
use Pragmatic\Debug\Drivers\LaraDumpsDriver;
use Pragmatic\Debug\Drivers\NullDriver;
use Pragmatic\Debug\Drivers\SilentLogDriver;
use Pragmatic\Debug\Enums\DebugMode;

final class DebugManager
{
    /**
     * Cache of instantiated drivers.
     */
    private array $drivers = [];

    /**
     * Resolved mode (after auto-detection).
     */
    private ?DebugMode $resolvedMode = null;

    public function __construct(
        private readonly DebugMode $mode
    ) {}

    /**
     * Get a debug driver instance.
     * Returns NullDriver if mode is Disabled.
     * Returns SilentLogDriver if mode is Silent.
     */
    public function driver(?string $name = null): DebugDriver
    {
        $resolvedMode = $this->resolveMode();

        // Disabled mode always returns NullDriver
        if ($resolvedMode === DebugMode::Disabled) {
            return $this->drivers['null'] ??= new NullDriver;
        }

        // Silent mode returns SilentLogDriver
        if ($resolvedMode === DebugMode::Silent) {
            return $this->drivers['silent-log'] ??= $this->createDriver('silent-log');
        }

        // Enabled mode returns requested driver (or default)
        $name ??= config('debug.default', 'laravel');

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->drivers[$name] = $this->createDriver($name);
    }

    /**
     * Get LaraDumps instance or NullDriver based on mode.
     */
    public function ds(): LaraDumps|LaravelLaraDumps|NullDriver
    {
        $resolvedMode = $this->resolveMode();

        // Disabled or Silent mode returns NullDriver
        if ($resolvedMode === DebugMode::Disabled || $resolvedMode === DebugMode::Silent) {
            return new NullDriver;
        }

        // Enabled mode returns LaraDumps
        $driver = $this->driver('laradumps');

        if (! $driver instanceof LaraDumpsDriver) {
            throw new InvalidArgumentException('LaraDumps driver is not configured properly.');
        }

        return $driver->ds();
    }

    /**
     * Check if debugging is currently enabled.
     */
    public function isEnabled(): bool
    {
        $resolvedMode = $this->resolveMode();

        return $resolvedMode === DebugMode::Enabled;
    }

    /**
     * Resolve the actual mode (convert Auto to Enabled/Disabled).
     */
    private function resolveMode(): DebugMode
    {
        if ($this->resolvedMode !== null) {
            return $this->resolvedMode;
        }

        if ($this->mode !== DebugMode::Auto) {
            return $this->resolvedMode = $this->mode;
        }

        return $this->resolvedMode = $this->autoModeToRealMode();
    }

    /**
     * Determine mode automatically based on Pennant flag, config, and APP_ENV.
     *
     * Priority:
     * 1. Pennant feature flag 'force-debug' (if available)
     * 2. config('debug.enabled')
     * 3. APP_ENV !== 'production'
     */
    private function autoModeToRealMode(): DebugMode
    {
        // Check Pennant feature flag first (if available)
        if (class_exists(Feature::class)) {
            $featureName = config('debug.pennant_feature', 'force-debug');

            try {
                if (Feature::active($featureName)) {
                    return DebugMode::Enabled;
                }
            } catch (\Throwable) {
                // Feature check failed, continue to config check
            }
        }

        // Fall back to configuration
        $enabled = config('debug.enabled', false);

        return $enabled ? DebugMode::Enabled : DebugMode::Disabled;
    }

    /**
     * Create a driver instance from configuration.
     */
    private function createDriver(string $name): DebugDriver
    {
        $config = config("debug.drivers.{$name}");

        if ($config === null) {
            throw new InvalidArgumentException("Debug driver [{$name}] is not configured.");
        }

        $driverClass = $config['driver'] ?? null;

        if ($driverClass === null || ! class_exists($driverClass)) {
            throw new InvalidArgumentException("Debug driver class for [{$name}] is not valid.");
        }

        $driver = new $driverClass;

        if (! $driver instanceof DebugDriver) {
            throw new InvalidArgumentException(
                "Debug driver [{$driverClass}] must implement DebugDriver interface."
            );
        }

        return $driver;
    }

    /**
     * Proxy method calls to the default driver.
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->driver()->$method(...$args);
    }
}
