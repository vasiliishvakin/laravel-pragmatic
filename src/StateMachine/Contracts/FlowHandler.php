<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Contracts;

use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Interface for handling state flow logic and side effects.
 *
 * FlowHandler is responsible for executing side effects when
 * entering or exiting states (e.g., sending messages, emails, notifications).
 */
interface FlowHandler
{
    /**
     * Called when entering a new state.
     *
     * @param  UnitEnum  $state  The state being entered
     * @param  array<string, mixed>  $data  Additional data for the transition
     * @param  Model  $entity  The entity transitioning
     */
    public function onEnter(UnitEnum $state, array $data, Model $entity): void;

    /**
     * Called when exiting a state (optional).
     *
     * @param  UnitEnum  $state  The state being exited
     * @param  array<string, mixed>  $data  Additional data for the transition
     * @param  Model  $entity  The entity transitioning
     */
    public function onExit(UnitEnum $state, array $data, Model $entity): void;
}
