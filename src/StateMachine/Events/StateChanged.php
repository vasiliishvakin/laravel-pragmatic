<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use UnitEnum;

/**
 * Event fired when a state transition occurs.
 */
class StateChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $entity,
        public readonly ?UnitEnum $fromState,
        public readonly UnitEnum $toState,
        public readonly array $data,
    ) {}
}
