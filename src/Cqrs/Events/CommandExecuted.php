<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Command;

/**
 * Event dispatched after a command is successfully executed.
 */
class CommandExecuted
{
    use Dispatchable;

    public function __construct(
        public readonly Command $command,
        public readonly mixed $result,
        public readonly float $executionTime,
    ) {}
}
