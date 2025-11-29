<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Examples;

/**
 * Example: Order state with strict transition rules.
 *
 * This demonstrates level 3 complexity - strict state machine
 * where transitions are controlled and validated.
 */
enum OrderState: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Check if transition to target state is allowed.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($target, [self::Paid, self::Cancelled], true),
            self::Paid => $target === self::Shipped,
            self::Shipped => $target === self::Completed,
            self::Completed, self::Cancelled => false, // final states
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает подтверждения',
            self::Confirmed => 'Подтверждён',
            self::Paid => 'Оплачен',
            self::Shipped => 'Отправлен',
            self::Completed => 'Завершён',
            self::Cancelled => 'Отменён',
        };
    }

    /**
     * Check if this is a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
