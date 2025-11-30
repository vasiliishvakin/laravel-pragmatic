<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Action;
use Throwable;

/**
 * Event dispatched when an action execution fails with an exception.
 */
class ActionFailed
{
    use Dispatchable;

    public function __construct(
        public readonly Action $action,
        public readonly Throwable $exception,
        public readonly float $executionTime,
    ) {}
}
