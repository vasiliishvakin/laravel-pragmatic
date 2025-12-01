<?php

namespace Pragmatic\Debug\Drivers;

use Pragmatic\Debug\Contracts\DebugDriver;

final class LogDriver implements DebugDriver
{
    /**
     * Dump variable(s) and continue execution.
     */
    public function dump(mixed ...$vars): mixed
    {
        foreach ($vars as $var) {
            \Log::debug(print_r($var, true));
        }

        return null;
    }

    /**
     * Dump variable(s) and die (stop execution).
     */
    public function dd(mixed ...$vars): never
    {
        $this->dump(...$vars);
        die();
    }
}
