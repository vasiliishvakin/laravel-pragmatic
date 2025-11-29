<?php

declare(strict_types=1);

namespace Pragmatic\Debug;

use InvalidArgumentException;
use Pragmatic\Debug\Contracts\DebugDriver;

final class DebugFactoryContainer
{
    /**
     * Cache of instantiated drivers.
     *
     * @var array<string, DebugManager>
     */
    private array $drivers = [];

    /**
     * Get or create a debug manager instance for the specified driver.
     */
    public function get(string $name): DebugManager
    {
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        $config = config("debug.drivers.{$name}");

        if ($config === null) {
            throw new InvalidArgumentException("Debug driver [{$name}] is not configured.");
        }

        $driverClass = $config['driver'] ?? null;

        if ($driverClass === null || ! class_exists($driverClass)) {
            throw new InvalidArgumentException("Debug driver class for [{$name}] is not valid.");
        }

        $driver = new $driverClass($config['options'] ?? []);

        if (! $driver instanceof DebugDriver) {
            throw new InvalidArgumentException(
                "Debug driver [{$driverClass}] must implement DebugDriver interface."
            );
        }

        $this->drivers[$name] = new DebugManager($this, $driver);

        return $this->drivers[$name];
    }

    /**
     * Check if a driver exists in the configuration.
     */
    public function hasDriver(string $name): bool
    {
        return config("debug.drivers.{$name}") !== null;
    }

    /**
     * Clear all cached driver instances.
     */
    public function clearCache(): void
    {
        $this->drivers = [];
    }
}
