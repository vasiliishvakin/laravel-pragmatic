<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Contracts;

interface CoreDebugDriver extends DebugDriver
{
    /**
     * Dump variable(s) and continue execution.
     */
    public function dump(mixed ...$vars): mixed;

    /**
     * Dump variable(s) and die (stop execution).
     */
    public function dd(mixed ...$vars): never;
}
