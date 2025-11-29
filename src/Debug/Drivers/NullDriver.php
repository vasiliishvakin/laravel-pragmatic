<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Drivers;

use Pragmatic\Debug\Contracts\DebugDriver;

final class NullDriver implements DebugDriver
{
    public function __call(string $method, array $args): mixed
    {
        // Do nothing and return null for any method called
        return null;
    }
}
