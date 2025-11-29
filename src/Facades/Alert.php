<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Alerts\AlertManager;

/**
 * @method static void add(\Modules\Toolbox\Alerts\AlertData $alert)
 * @method static void push(\Modules\Toolbox\Enums\AlertType $type, string $message)
 * @method static void success(string $message)
 * @method static void error(string $message)
 * @method static void info(string $message)
 * @method static void warning(string $message)
 * @method static array<string, \Modules\Toolbox\Alerts\AlertData> get(\Modules\Toolbox\Enums\AlertType $type)
 * @method static array<string, \Modules\Toolbox\Alerts\AlertData> peek(\Modules\Toolbox\Enums\AlertType $type)
 * @method static iterable<string, \Modules\Toolbox\Alerts\AlertData> peekAll()
 * @method static iterable<string, \Modules\Toolbox\Alerts\AlertData> all()
 * @method static void forget(\Modules\Toolbox\Enums\AlertType $type)
 * @method static void clear()
 * @method static bool has(\Modules\Toolbox\Enums\AlertType $type)
 * @method static bool hasAny()
 * @method static int count(\Modules\Toolbox\Enums\AlertType $type)
 *
 * @see \Modules\Toolbox\Alerts\AlertManager
 */
class Alert extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AlertManager::class;
    }
}
