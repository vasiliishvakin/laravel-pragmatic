<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Json\JsonManager;

class Json extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JsonManager::class;
    }
}
