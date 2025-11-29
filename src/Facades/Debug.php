<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Debug\DebugManager;

/**
 * @method static \Modules\Toolbox\Debug\DebugManager driver(?string $name = null)
 * @method static \Modules\Toolbox\Debug\DebugManager core()
 * @method static \Modules\Toolbox\Debug\DebugManager ds()
 * @method static \Modules\Toolbox\Debug\DebugManager log(?string $level = null)
 * @method static \Modules\Toolbox\Debug\DebugManager dump(mixed ...$vars)
 * @method static never dd(mixed ...$vars)
 * @method static never die(string $message = '')
 * @method static bool isEnabled()
 * @method static void enable()
 * @method static void disable()
 * @method static void resetState()
 *
 * @see \Modules\Toolbox\Debug\DebugManager
 */
class Debug extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DebugManager::class;
    }
}
