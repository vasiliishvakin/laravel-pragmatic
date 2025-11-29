<?php

declare(strict_types=1);

namespace Pragmatic\Support;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * FactoryContainer — non-static registry + factory hybrid.
 * Behaves like a mini IoC container: can bind factories and singletons.
 */
class FactoryContainer
{
    protected Container $app;

    /** @var array<string, Closure|string> */
    protected array $bindings = [];

    /** @var array<string, Closure|string> */
    protected array $singletons = [];

    /** @var array<string, mixed> */
    protected array $instances = [];

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Register a singleton factory (cached instance).
     */
    public function singleton(string $key, Closure|string $factory): static
    {
        $this->singletons[$key] = $factory;

        return $this;
    }

    /**
     * Register a factory (new instance every call).
     */
    public function bind(string $key, Closure|string $factory): static
    {
        $this->bindings[$key] = $factory;

        return $this;
    }

    /**
     * Resolve instance (singleton or factory).
     */
    public function get(string $key): mixed
    {
        // Return cached singleton if exists
        if (array_key_exists($key, $this->instances)) {
            return $this->instances[$key];
        }

        // Resolve factory (singleton or binding)
        $factory = $this->singletons[$key] ?? $this->bindings[$key] ?? null;

        if ($factory === null) {
            throw new InvalidArgumentException("No factory registered for [$key].");
        }

        $instance = $this->resolve($factory);

        // Cache singleton instance
        if (isset($this->singletons[$key])) {
            $this->instances[$key] = $instance;
        }

        return $instance;
    }

    /**
     * Always create a new instance (ignores singleton cache).
     */
    public function make(string $key): mixed
    {
        $factory = $this->singletons[$key] ?? $this->bindings[$key] ?? null;

        if ($factory === null) {
            throw new InvalidArgumentException("No factory registered for [$key].");
        }

        return $this->resolve($factory);
    }

    /**
     * Forget existing singleton instance (force recreation on next get()).
     */
    public function forget(string $key): void
    {
        unset($this->instances[$key]);
    }

    /**
     * Internal resolver using Laravel container.
     */
    protected function resolve(Closure|string $factory): mixed
    {
        return $factory instanceof Closure
            ? $factory($this->app)
            : $this->app->make($factory);
    }
}
