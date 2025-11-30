<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Action;

/**
 * Event dispatched before an action is executed.
 */
class ActionExecuting
{
    use Dispatchable;

    public function __construct(
        public readonly Action $action,
    ) {}
}
