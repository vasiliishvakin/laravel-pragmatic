<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Command;

/**
 * Event dispatched before a command is executed.
 */
class CommandExecuting
{
    use Dispatchable;

    public function __construct(
        public readonly Command $command,
    ) {}
}
