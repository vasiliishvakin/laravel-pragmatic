<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Contracts;

interface DebugManagerInstance
{
    /**
     * Get a debug driver instance.
     */
    public function driver(?string $name = null): static;

    /**
     * Get the raw debug driver.
     */
    public function rawDriver(): DebugDriver;

    /**
     * Check if a driver has been set.
     */
    public function hasDriver(): bool;

    /**
     * Get the current instance or default driver.
     */
    public function instance(): static;

    /**
     * Check if debugging is enabled.
     */
    public function isEnabled(): bool;
}
