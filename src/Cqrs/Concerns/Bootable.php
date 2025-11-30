<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Concerns;

/**
 * Trait for bootable CQRS operations.
 *
 * Provides two-phase initialization:
 * 1. Constructor - receives data parameters
 * 2. boot() - optional dependency injection phase
 */
trait Bootable
{
    /**
     * Indicates if the boot method has been called.
     */
    private bool $isBooted = false;

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
