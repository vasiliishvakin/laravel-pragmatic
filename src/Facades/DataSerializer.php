<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Data\DataSerializerService;

class DataSerializer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DataSerializerService::class;
    }
}
