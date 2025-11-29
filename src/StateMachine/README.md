# StateMachine Module

Universal state management module for Laravel models with three levels of complexity.

## Features

- ✅ **Level 1**: Simple key-value storage (`set/get`)
- ✅ **Level 2**: Enum-based states without strict rules (type-safe transitions)
- ✅ **Level 3**: Enum-based states with transition rules (state machine)
- ✅ **Polymorphic**: Works with any Eloquent model
- ✅ **Laravel-native**: Events, transactions, Eloquent relations
- ✅ **Lightweight**: No heavy dependencies
- ✅ **Type-safe**: Full PHP 8.3+ enum support

## Installation

The module is already available in `modules/Toolbox/StateMachine/`.

Run migration:
```bash
php artisan migrate
```

## Quick Start

### 1. Add trait to your model

```php
use Modules\Toolbox\StateMachine\Traits\HasStateMachine;

class Order extends Model
{
    use HasStateMachine;
}
```

### 2. Use simple key-value storage (Level 1)

```php
$order->state()->set('payment_method', 'stripe');
$order->state()->set('subtotal', 100.50);

$paymentMethod = $order->state()->get('payment_method'); // 'stripe'
$total = $order->state()->get('total', 0); // default: 0
```

### 3. Use enum states (Level 2)

```php
enum OrderState: string {
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
}

$order->state()->transitionTo(OrderState::Paid);
$order->state()->is(OrderState::Paid); // true
$order->state()->in(OrderState::Paid, OrderState::Shipped); // true
```

### 4. Use state machine with rules (Level 3)

```php
enum OrderState: string {
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';

    public function canTransitionTo(self $target): bool {
        return match($this) {
            self::Pending => $target === self::Paid,
            self::Paid => $target === self::Shipped,
            self::Shipped => false, // final state
        };
    }
}

$order->state()->transitionTo(OrderState::Paid); // ✓ OK
$order->state()->transitionTo(OrderState::Shipped); // ✓ OK
$order->state()->transitionTo(OrderState::Pending); // ✗ InvalidTransitionException
```

## API Reference

### StateManager Methods

#### Key-Value Storage (Level 1)

```php
// Set a value
$model->state()->set('key', 'value');

// Get a value
$model->state()->get('key', $default = null);

// Check if key exists
$model->state()->has('key'); // bool

// Remove a key
$model->state()->forget('key');

// Get all data
$model->state()->all(); // array

// Clear all data
$model->state()->clear();

// Supports dot notation
$model->state()->set('user.name', 'John');
$model->state()->get('user.name'); // 'John'
```

#### Enum State Management (Levels 2 & 3)

```php
// Transition to a new state
$model->state()->transitionTo(MyState::Active, ['reason' => 'approved']);

// Get current state
$current = $model->state()->current(); // MyState enum | null

// Check current state
$model->state()->is(MyState::Active); // bool

// Check if in multiple states
$model->state()->in(MyState::Active, MyState::Pending); // bool

// Reset state to null
$model->state()->reset();

// Delete state record entirely
$model->state()->delete();
```

#### Flow Handlers (Optional)

```php
// Use a flow handler for side effects
$model->state()->useFlow(new MyFlow())->transitionTo(MyState::Active);
```

## Advanced Usage

### Flow Handlers

Flow handlers execute side effects when entering/exiting states:

```php
use Modules\Toolbox\StateMachine\Contracts\FlowHandler;

class OrderFlow implements FlowHandler
{
    public function onEnter(UnitEnum $state, array $data, Model $entity): void
    {
        if (!$state instanceof OrderState) return;

        match($state) {
            OrderState::Paid => $this->sendPaymentConfirmation($entity),
            OrderState::Shipped => $this->notifyShipping($entity),
            default => null,
        };
    }

    public function onExit(UnitEnum $state, array $data, Model $entity): void
    {
        // Optional cleanup
    }
}

// Usage
$order->state()
    ->useFlow(new OrderFlow())
    ->transitionTo(OrderState::Paid); // triggers onEnter
```

### Events

The module dispatches events for monitoring:

```php
use Modules\Toolbox\StateMachine\Events\StateChanged;
use Modules\Toolbox\StateMachine\Events\TransitionFailed;

// Listen to state changes
Event::listen(StateChanged::class, function($event) {
    Log::info('State changed', [
        'model' => get_class($event->entity),
        'from' => $event->fromState?->name,
        'to' => $event->toState->name,
    ]);
});

// Listen to failed transitions
Event::listen(TransitionFailed::class, function($event) {
    Log::error('Transition failed', [
        'model' => get_class($event->entity),
        'from' => $event->fromState?->name,
        'to' => $event->toState->name,
        'error' => $event->exception->getMessage(),
    ]);
});
```

### Enum with Helper Methods

Add custom methods to your enums:

```php
enum OrderState: string {
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';

    public function label(): string {
        return match($this) {
            self::Pending => 'Ожидает оплаты',
            self::Paid => 'Оплачен',
            self::Shipped => 'Отправлен',
            self::Completed => 'Завершён',
        };
    }

    public function isFinal(): bool {
        return $this === self::Completed;
    }

    public function canTransitionTo(self $target): bool {
        // Your transition rules
    }
}
```

## Examples

See `modules/Toolbox/StateMachine/Examples/` for complete examples:

- `OrderState.php` - Order state with strict transition rules (Level 3)
- `ChatState.php` - Chat bot state without rules (Level 2)
- `OrderFlow.php` - Flow handler for order state transitions

## Use Cases

### 1. Quiz/Learning Progress (Level 1)
```php
$quiz->state()->set('current_question', 5);
$quiz->state()->set('score', 80);
$quiz->state()->set('answers', ['a', 'b', 'c']);
```

### 2. Chatbot Conversations (Level 2)
```php
enum ChatState: string {
    case Idle = 'idle';
    case AskingName = 'asking_name';
    case AskingEmail = 'asking_email';
}

$chat->state()->transitionTo(ChatState::AskingName);
$chat->state()->set('attempts', 3);
```

### 3. Order Processing (Level 3)
```php
enum OrderState: string {
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';

    public function canTransitionTo(self $target): bool { /* rules */ }
}

$order->state()
    ->useFlow(new OrderFlow())
    ->transitionTo(OrderState::Paid, ['payment_method' => 'stripe']);
```

## Testing

Run tests:
```bash
php artisan test --filter=StateManager
```

All 16 tests covering:
- Key-value storage
- Enum state transitions
- Transition rules validation
- Events dispatching
- Nested data with dot notation
- Model deletion cascade

## Architecture

```
modules/Toolbox/StateMachine/
├── Contracts/
│   ├── Stateful.php              # Interface for stateful models
│   └── FlowHandler.php           # Interface for flow handlers
├── Models/
│   └── State.php                 # Polymorphic state model
├── Traits/
│   └── HasStateMachine.php       # Trait for models
├── Events/
│   ├── StateChanged.php          # Dispatched on successful transition
│   └── TransitionFailed.php      # Dispatched on failed transition
├── Exceptions/
│   └── InvalidTransitionException.php
├── Examples/                     # Example implementations
│   ├── OrderState.php
│   ├── ChatState.php
│   └── OrderFlow.php
├── StateManager.php              # Core state manager
└── README.md                     # This file
```

## Database Schema

The `states` table uses polymorphic relations:

```sql
CREATE TABLE states (
    id BIGINT PRIMARY KEY,
    stateful_type VARCHAR(255),  -- Model class
    stateful_id BIGINT,           -- Model ID
    current_state VARCHAR(255),   -- Enum state (Class@Name)
    data JSON,                    -- Key-value data
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX(stateful_type, stateful_id)
);
```

## Migration from Existing ChatState

If you have existing `chat_states` table, you can migrate:

1. Keep existing `ChatStateManager` as adapter
2. Gradually migrate to new `StateManager` API
3. Update chatbot scenarios to use new trait

## License

Part of the Lingo project internal toolbox.
