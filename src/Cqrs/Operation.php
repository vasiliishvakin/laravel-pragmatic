<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

/**
 * Base class for all CQRS operations.
 *
 * Provides common functionality for Query, Command, and Action:
 * - Two-phase initialization (constructor + boot)
 * - Middleware management (global, per-class, runtime)
 * - Factory method for fluent API
 *
 * All operations must implement execute() method which performs
 * the core operation logic.
 *
 * Two-phase initialization:
 * 1. Constructor - receives data parameters
 * 2. boot() - optional dependency injection phase (called automatically by Bus)
 *
 * Middleware execution order:
 * 1. Global middleware (from config)
 * 2. Per-class middleware (from middleware() method)
 * 3. Runtime middleware (from withMiddleware() method)
 */
abstract class Operation
{
    /**
     * Indicates if the boot method has been called.
     */
    private bool $isBooted = false;

    /**
     * Runtime middleware to be applied to this specific operation instance.
     *
     * @var array<int, class-string|object>
     */
    private array $runtimeMiddleware = [];

    /**
     * Middleware classes to exclude from execution.
     *
     * @var array<int, class-string>
     */
    private array $excludedMiddleware = [];

    /**
     * Execute the operation and return result.
     *
     * Must be implemented in child classes.
     * Dependencies are automatically injected via type-hints.
     */
    abstract public function execute(): mixed;

    /**
     * Factory method for fluent API construction.
     *
     * @param  mixed  ...$params  Constructor parameters
     */
    public static function make(mixed ...$params): static
    {
        return new static(...$params);
    }

    // =========================================================================
    // Boot lifecycle
    // =========================================================================

    /**
     * Optional boot method for dependency injection.
     *
     * Called automatically by Bus before execute().
     * Override this method to inject dependencies that cannot be passed via constructor.
     */
    public function boot(): void
    {
        // Optional: override in child classes
    }

    /**
     * Mark the operation as booted.
     *
     * @internal Called by Bus
     */
    public function markAsBooted(): void
    {
        $this->isBooted = true;
    }

    /**
     * Check if the boot method has been called.
     */
    public function isBooted(): bool
    {
        return $this->isBooted;
    }

    // =========================================================================
    // Middleware management
    // =========================================================================

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
