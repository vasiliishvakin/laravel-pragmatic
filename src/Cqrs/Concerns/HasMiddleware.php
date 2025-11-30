<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Concerns;

/**
 * Trait for managing middleware in CQRS operations.
 *
 * Provides functionality for:
 * - Per-class middleware registration
 * - Runtime middleware addition (fluent API)
 * - Middleware exclusion (class-level and runtime)
 */
trait HasMiddleware
{
    /**
     * Runtime middleware to be applied to this specific operation instance.
     */
    private array $runtimeMiddleware = [];

    /**
     * Middleware classes to exclude from execution.
     */
    private array $excludedMiddleware = [];

    /**
     * Get per-class middleware for this operation.
     *
     * Override this method in child classes to define middleware
     * that should always be applied to this operation type.
     *
     * @return array<int, class-string|object> Array of middleware class names or instances
     */
    public function middleware(): array
    {
        return [];
    }

    /**
     * Add runtime middleware to this operation instance (fluent API).
     *
     * Middleware can be specified as:
     * - Class name string: 'App\Middleware\MyMiddleware'
     * - Array of class names: ['Middleware1', 'Middleware2']
     * - Middleware instance: new MyMiddleware()
     * - Array of instances: [new Middleware1(), new Middleware2()]
     *
     * @param  array<int, class-string|object>|class-string|object  $middleware
     */
    public function withMiddleware(array|string|object $middleware): static
    {
        $this->runtimeMiddleware = array_merge(
            $this->runtimeMiddleware,
            is_array($middleware) ? $middleware : [$middleware]
        );

        return $this;
    }

    /**
     * Get runtime middleware for this operation instance.
     *
     * @internal Used by QueryBus/CommandBus
     *
     * @return array<int, class-string|object>
     */
    public function getRuntimeMiddleware(): array
    {
        return $this->runtimeMiddleware;
    }

    /**
     * Get middleware classes to exclude from this operation.
     *
     * Override this method in child classes to exclude specific middleware
     * from execution for this operation type.
     *
     * @return array<int, class-string> Array of middleware class names to exclude
     */
    public function excludeMiddleware(): array
    {
        return [];
    }

    /**
     * Exclude specific middleware from execution (fluent API).
     *
     * Middleware can be specified as:
     * - Class name string: 'App\Middleware\MyMiddleware'
     * - Array of class names: ['Middleware1', 'Middleware2']
     *
     * @param  array<int, class-string>|class-string  $middleware
     */
    public function withoutMiddleware(array|string $middleware): static
    {
        $this->excludedMiddleware = array_merge(
            $this->excludedMiddleware,
            is_array($middleware) ? $middleware : [$middleware]
        );

        return $this;
    }

    /**
     * Get excluded middleware for this operation instance.
     *
     * @internal Used by QueryBus/CommandBus
     *
     * @return array<int, class-string>
     */
    public function getExcludedMiddleware(): array
    {
        return array_merge(
            $this->excludeMiddleware(),
            $this->excludedMiddleware
        );
    }
}
