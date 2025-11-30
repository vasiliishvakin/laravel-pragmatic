<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Drivers;

use Pragmatic\Debug\Contracts\CoreDebugDriver;

final class CoreDriver implements CoreDebugDriver
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
