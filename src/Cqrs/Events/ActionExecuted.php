<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Action;

/**
 * Event dispatched after an action is successfully executed.
 */
class ActionExecuted
{
    use Dispatchable;

    public function __construct(
        public readonly Action $action,
        public readonly mixed $result,
        public readonly float $executionTime,
    ) {}
}
