<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Drivers;

use Pragmatic\Debug\Contracts\DebugDriver;

final class NullDriver implements DebugDriver
{
    public function __call(string $method, array $args): self
    {
        return $this;
    }

    public function __get(string $name): null
    {
        return null;
    }

    public function __set(string $name, mixed $value): void
    {
        // Do nothing
    }

    public function dump(mixed ...$vars): mixed
    {
        return null;
    }

    public function dd(mixed ...$vars)
    {
        // Do nothing
    }
}
