<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Data\DataFactoryService;

class DataFactory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DataFactoryService::class;
    }
}
