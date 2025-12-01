<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Drivers;

use Pragmatic\Debug\Contracts\DebugDriver;

final class PhpDriver implements DebugDriver
{
    public function var_dump(mixed ...$vars): void
    {
        var_dump(...$vars);
    }

    public function print_r(mixed ...$vars): void
    {
        print_r(...$vars);
    }

    public function die(string|int $status = 0): never
    {
        die($status);
    }
}
