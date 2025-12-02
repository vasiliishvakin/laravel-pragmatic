<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Drivers;

use LaraDumps\LaraDumps\LaraDumps as LaravelLaraDumps;
use LaraDumps\LaraDumpsCore\LaraDumps;
use Pragmatic\Debug\Contracts\DebugDriver;

final class LaraDumpsDriver implements DebugDriver
{
    public function ds(mixed ...$args): LaraDumps|LaravelLaraDumps
    {
        throw_unless(function_exists('ds'), \RuntimeException::class, 'LaraDumps is not installed. Please install it via composer require laradumps/laradumps');

        return ds();
    }

    public function dump(mixed ...$vars): mixed
    {
        return $this->ds(...$vars);
    }

    public function dd(mixed ...$vars): void
    {
        $this->ds(...$vars)->die();
    }
}
