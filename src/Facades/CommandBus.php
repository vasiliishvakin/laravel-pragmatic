<?php

declare(strict_types=1);

namespace Pragmatic\Facades;

use Illuminate\Support\Facades\Facade;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\CommandBus as CommandBusBase;

/**
 * @method static mixed execute(Command $command)
 *
 * @see \Pragmatic\Cqrs\CommandBus
 */
class CommandBus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CommandBusBase::class;
    }
}
