<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Command;
use Throwable;

/**
 * Event dispatched when a command execution fails.
 */
class CommandFailed
{
    use Dispatchable;

    public function __construct(
        public readonly Command $command,
        public readonly Throwable $exception,
        public readonly float $executionTime,
    ) {}
}
