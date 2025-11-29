<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Pragmatic\StateMachine\Models\State as StateModel;
use Pragmatic\StateMachine\StateManager;

/**
 * Trait for models that support state management.
 *
 * Usage:
 * ```php
 * class Order extends Model {
 *     use HasStateMachine;
 * }
 *
 * $order->state()->transitionTo(OrderState::Paid);
 * $order->state()->set('payment_method', 'stripe');
 * ```
 */
trait HasStateMachine
{
    /**
     * Get the state manager instance.
     */
    public function state(): StateManager
    {
        return new StateManager($this);
    }

    /**
     * Polymorphic relation to the state model.
     */
    public function stateRecord(): MorphOne
    {
        return $this->morphOne(StateModel::class, 'stateful');
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasStateMachine(): void
    {
        // Clean up state when model is deleted
        static::deleting(function ($model) {
            $model->stateRecord()->delete();
        });
    }
}
