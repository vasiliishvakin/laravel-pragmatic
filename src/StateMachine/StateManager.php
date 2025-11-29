<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Pragmatic\StateMachine\Contracts\FlowHandler;
use Pragmatic\StateMachine\Events\StateChanged;
use Pragmatic\StateMachine\Events\TransitionFailed;
use Pragmatic\StateMachine\Exceptions\InvalidTransitionException;
use Pragmatic\StateMachine\Models\State;
use Throwable;
use UnitEnum;

/**
 * Universal state manager for Laravel models.
 *
 * Supports three levels of complexity:
 * 1. Simple key-value storage: set('key', 'value') / get('key')
 * 2. Enum states without strict rules: transitionTo(MyState::Active)
 * 3. Enum states with transition rules: enum with canTransitionTo() method
 */
class StateManager
{
    protected ?FlowHandler $flow = null;

    public function __construct(
        protected readonly Model $entity,
    ) {}

    /**
     * Set a data value (key-value storage).
     */
    public function set(string $key, mixed $value): void
    {
        $state = $this->getStateRecord();
        $data = $state->data ?? [];
        data_set($data, $key, $value);
        $state->update(['data' => $data]);
        $this->entity->load('stateRecord');
    }

    /**
     * Get a data value (key-value storage).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $state = $this->entity->stateRecord;

        return $state ? data_get($state->data, $key, $default) : $default;
    }

    /**
     * Check if a key exists in data.
     */
    public function has(string $key): bool
    {
        $state = $this->entity->stateRecord;

        return $state && data_get($state->data, $key) !== null;
    }

    /**
     * Remove a data key.
     */
    public function forget(string $key): void
    {
        $state = $this->entity->stateRecord;

        if (! $state) {
            return;
        }

        $data = $state->data ?? [];
        data_forget($data, $key);
        $state->update(['data' => $data]);
        $this->entity->load('stateRecord');
    }

    /**
     * Get all data.
     */
    public function all(): array
    {
        return $this->entity->stateRecord?->data ?? [];
    }

    /**
     * Clear all data.
     */
    public function clear(): void
    {
        $this->entity->stateRecord?->update(['data' => null]);
    }

    /**
     * Transition to a new enum state.
     *
     * @param  UnitEnum  $state  Target state
     * @param  array<string, mixed>  $data  Additional data to store
     *
     * @throws InvalidTransitionException If transition is not allowed
     */
    public function transitionTo(UnitEnum $state, array $data = []): void
    {
        $currentState = $this->current();

        // Check if transition is allowed (if enum has canTransitionTo method)
        if ($currentState && method_exists($currentState, 'canTransitionTo')) {
            if (! $currentState->canTransitionTo($state)) {
                $exception = InvalidTransitionException::fromStates($currentState, $state);
                TransitionFailed::dispatch($this->entity, $currentState, $state, $exception);

                throw $exception;
            }
        }

        try {
            DB::transaction(function () use ($state, $data, $currentState) {
                // Call onExit for current state
                if ($currentState && $this->flow) {
                    $this->flow->onExit($currentState, $data, $this->entity);
                }

                // Update state
                $stateRecord = $this->getStateRecord();
                $existingData = $stateRecord->data ?? [];
                $stateRecord->update([
                    'current_state' => $this->enumToString($state),
                    'data' => array_merge($existingData, $data),
                ]);

                // Reload relationship
                $this->entity->load('stateRecord');

                // Call onEnter for new state
                if ($this->flow) {
                    $this->flow->onEnter($state, $data, $this->entity);
                }

                // Fire event
                StateChanged::dispatch($this->entity, $currentState, $state, $data);
            });
        } catch (Throwable $e) {
            if (! $e instanceof InvalidTransitionException) {
                TransitionFailed::dispatch($this->entity, $currentState, $state, $e);
            }

            throw $e;
        }
    }

    /**
     * Get current state as enum.
     */
    public function current(): ?UnitEnum
    {
        $stateString = $this->entity->stateRecord?->current_state;

        return $stateString ? $this->stringToEnum($stateString) : null;
    }

    /**
     * Check if current state matches given state.
     */
    public function is(UnitEnum $state): bool
    {
        $current = $this->current();

        return $current && $current === $state;
    }

    /**
     * Check if current state is in given states.
     */
    public function in(UnitEnum ...$states): bool
    {
        $current = $this->current();

        if (! $current) {
            return false;
        }

        foreach ($states as $state) {
            if ($current === $state) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reset state to null.
     */
    public function reset(): void
    {
        $this->entity->stateRecord?->update(['current_state' => null, 'data' => null]);
    }

    /**
     * Delete state record entirely.
     */
    public function delete(): void
    {
        $this->entity->stateRecord?->delete();
    }

    /**
     * Use a flow handler for this state machine.
     */
    public function useFlow(FlowHandler $flow): self
    {
        $this->flow = $flow;

        return $this;
    }

    /**
     * Get the state record for this entity (creates if not exists).
     */
    protected function getStateRecord(): State
    {
        if (! $this->entity->relationLoaded('stateRecord')) {
            $this->entity->load('stateRecord');
        }

        if (! $this->entity->stateRecord) {
            $this->entity->stateRecord()->create([]);
            $this->entity->load('stateRecord');
        }

        return $this->entity->stateRecord;
    }

    /**
     * Convert enum to string for storage.
     */
    protected function enumToString(UnitEnum $enum): string
    {
        return $enum::class.'@'.$enum->name;
    }

    /**
     * Convert stored string back to enum.
     */
    protected function stringToEnum(string $string): ?UnitEnum
    {
        [$class, $name] = explode('@', $string, 2);

        if (! enum_exists($class)) {
            return null;
        }

        return constant("{$class}::{$name}");
    }
}
