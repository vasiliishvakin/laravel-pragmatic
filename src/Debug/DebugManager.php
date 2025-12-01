<?php

declare(strict_types=1);

namespace Pragmatic\Debug;

use BadMethodCallException;
use InvalidArgumentException;
use Laravel\Pennant\Feature;
use Pest\Plugins\Parallel\Handlers\Laravel;
use Pragmatic\Debug\Contracts\DebugDriver;
use Pragmatic\Debug\Contracts\DebugManagerInstance;
use Pragmatic\Debug\Drivers\NullDriver;
use Pragmatic\Debug\Enums\DebugMode;

final class DebugManager
{
    private array $drivers = [];

    public function __construct(
        private DebugMode $mode,
        private DebugFactoryContainer $factory,
        private ?DebugDriver $defaultDriver = null
    ) {}



    /**
     * Get a debug driver instance.
     */
    public function driver(?string $name = null): DebugDriver
    {
        $name = $name ?? config('debug.default');

        if (! $this->hasDriver($name)) {
            throw new InvalidArgumentException("Debug driver [{$name}] is not configured.");
        }

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }
    }

    private function getDriverInstance(string $name): DebugDriver
    {
        if ($this)

            $driverClass = config("debug.drivers.{$name}.class");

        if (! class_exists($driverClass)) {
            throw new InvalidArgumentException("Debug driver class [{$driverClass}] does not exist.");
        }

        return new $driverClass();
    }

    private function defaultDriver(): string
    {
        $defaultDriver = config('debug.default');
        throw_unless($defaultDriver, InvalidArgumentException::class, 'No default debug driver configured.');
        return $defaultDriver;
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
    public function enable(): self
    {
        $this->mode = DebugMode::Enabled;
        return $this;
    }

    /**
     * Disable debugging (sets runtime flag).
     */
    public function disable(): self
    {
        $this->mode = DebugMode::Disabled;
        return $this;
    }

    public function silent(): self {}

    /**
     * Reset cached enabled state.
     */
    public function resetMode(): self
    {
        $mode = config('debug.mode');
        $this->mode = DebugMode::from($mode);
        return $this;
    }

    private function autoModeToRealMode(): DebugMode
    {
        //some code based on APP_ENV Penent flag or /end request param, in feature, now mock
        if ($this->isEnabled()) {
            return DebugMode::Enabled;
        }

        return DebugMode::Disabled;
    }

    private function driverByMode(): DebugDriver
    {
        $mode = $this->mode;

        if ($mode === DebugMode::Auto) {
            $mode = $this->autoModeToRealMode();
        }

        if ($mode === DebugMode::Disabled) {
            return new NullDriver();
        }

        return $this->driver();
    }

    public function dump(mixed ...$vars): mixed
    {
        if ($this->mode === DebugMode::Disabled) {
            return null;
        }

        return $this->driver()->dump(...$vars);
    }

}
