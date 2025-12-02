<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Drivers;

final class SilentLogDriver extends LogDriver
{
    /**
     * Dump variable(s) and not die because it's silent.
     */
    public function dd(mixed ...$vars): void
    {
        $this->dump(...$vars);
    }

    /**
     * Handle any method call by logging silently.
     */
    public function __call(string $method, array $args): mixed
    {
        $this->dump(...$args);

        return $this;
    }
}
