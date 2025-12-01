<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Drivers;

use Pragmatic\Debug\Contracts\DebugDriver;


final class LaravelDriver implements DebugDriver
{
    /**
     * Dump variable(s) and continue execution.
     */
    public function dump(mixed ...$vars): mixed
    {
        return dump(...$vars);
    }

    /**
     * Dump variable(s) and die (stop execution).
     */
    public function dd(mixed ...$vars): never
    {
        dd(...$vars);
    }
}
