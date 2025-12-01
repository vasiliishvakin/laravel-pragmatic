<?php

namespace Pragmatic\Debug\Drivers;

use Pragmatic\Debug\Contracts\DebugDriver;

final class SilentLogDriver extends LogDriver
{


    /**
     * Dump variable(s) and not die because it's silent.
     */
    public function dd(mixed ...$vars): never
    {
        $this->dump(...$vars);
    }
}
