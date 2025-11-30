<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

/**
 * Operation class with two-phase initialization support.
 *
 * Extends BaseOperation with boot lifecycle functionality:
 * - Two-phase initialization (constructor + boot)
 *
 * Two-phase initialization:
 * 1. Constructor - receives data parameters
 * 2. boot() - optional dependency injection phase (called automatically by Bus)
 *
 * This class is intended for Query and Command operations that require
 * dependency injection before execution.
 */
abstract class Operation extends BaseOperation
{
    /**
     * Indicates if the boot method has been called.
     */
    private bool $isBooted = false;

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
}
