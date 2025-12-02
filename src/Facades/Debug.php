<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Debug\DebugManager;

/**
 * @method static \Pragmatic\Debug\Contracts\DebugDriver driver(?string $name = null) Get a debug driver instance
 * @method static \LaraDumps\LaraDumpsCore\LaraDumps|\LaraDumps\LaraDumps\LaraDumps|\Pragmatic\Debug\Drivers\NullDriver ds() Get LaraDumps instance
 * @method static bool isEnabled() Check if debugging is currently enabled
 * @method static mixed dump(mixed ...$vars) Dump variable(s) and continue execution
 * @method static void dd(mixed ...$vars) Dump variable(s) and die
 *
 * @see \Pragmatic\Debug\DebugManager
 */
class Debug extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DebugManager::class;
    }
}
