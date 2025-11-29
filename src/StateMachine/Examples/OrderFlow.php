<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Examples;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Pragmatic\StateMachine\Contracts\FlowHandler;
use UnitEnum;

/**
 * Example: Flow handler for Order state transitions.
 *
 * Handles side effects when entering/exiting order states.
 */
class OrderFlow implements FlowHandler
{
    public function onEnter(UnitEnum $state, array $data, Model $entity): void
    {
        if (! $state instanceof OrderState) {
            return;
        }

        match ($state) {
            OrderState::Confirmed => $this->sendConfirmationEmail($entity, $data),
            OrderState::Paid => $this->processPayment($entity, $data),
            OrderState::Shipped => $this->notifyShipping($entity, $data),
            OrderState::Completed => $this->sendCompletionEmail($entity, $data),
            OrderState::Cancelled => $this->processCancellation($entity, $data),
            default => null,
        };
    }

    public function onExit(UnitEnum $state, array $data, Model $entity): void
    {
        // Optional: handle cleanup when leaving a state
        Log::info('Exiting order state', [
            'order_id' => $entity->id,
            'from_state' => $state->value,
        ]);
    }

    protected function sendConfirmationEmail(Model $order, array $data): void
    {
        // Send confirmation email
        Log::info('Sending confirmation email', ['order_id' => $order->id]);
    }

    protected function processPayment(Model $order, array $data): void
    {
        // Process payment
        Log::info('Processing payment', ['order_id' => $order->id, 'data' => $data]);
    }

    protected function notifyShipping(Model $order, array $data): void
    {
        // Notify shipping
        Log::info('Notifying shipping', ['order_id' => $order->id]);
    }

    protected function sendCompletionEmail(Model $order, array $data): void
    {
        // Send completion email
        Log::info('Sending completion email', ['order_id' => $order->id]);
    }

    protected function processCancellation(Model $order, array $data): void
    {
        // Process cancellation (refunds, notifications, etc.)
        Log::info('Processing cancellation', ['order_id' => $order->id, 'reason' => $data['reason'] ?? null]);
    }
}
