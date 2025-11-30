<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Cqrs\Query;
use Pragmatic\Cqrs\QueryBus as QueryBusBase;

/**
 * @method static mixed execute(Query $query)
 *
 * @see \Pragmatic\Cqrs\QueryBus
 */
class QueryBus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QueryBusBase::class;
    }
}
