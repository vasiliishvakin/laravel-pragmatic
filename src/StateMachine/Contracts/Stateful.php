<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Contracts;

use Pragmatic\StateMachine\StateManager;

/**
 * Interface for models that can have state management.
 *
 * @see \Modules\Toolbox\StateMachine\Traits\HasStateMachine
 */
interface Stateful
{
    /**
     * Get the state manager instance for this entity.
     */
    public function state(): StateManager;
}
