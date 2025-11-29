<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Support\EnumHelperService;

class EnumHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EnumHelperService::class;
    }
}
