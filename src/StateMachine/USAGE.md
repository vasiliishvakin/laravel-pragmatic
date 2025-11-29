# StateMachine - Quick Usage Guide

## 5-Minute Setup

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Add Trait to Model
```php
use Modules\Toolbox\StateMachine\Traits\HasStateMachine;

class YourModel extends Model {
    use HasStateMachine;
}
```

### 3. Choose Your Level

## Level 1: Simple Storage (No enum needed)
Perfect for: quiz progress, user preferences, temporary data

```php
// Set
$model->state()->set('score', 100);
$model->state()->set('level', 5);

// Get
$score = $model->state()->get('score');
$level = $model->state()->get('level', 1); // default = 1

// Check
if ($model->state()->has('score')) { }

// All data
$all = $model->state()->all();

// Nested
$model->state()->set('user.profile.age', 25);
$age = $model->state()->get('user.profile.age');
```

## Level 2: Enum States (Flexible)
Perfect for: chatbots, wizards, multi-step forms

```php
// 1. Create enum
enum ChatState: string {
    case Idle = 'idle';
    case AskingName = 'asking_name';
    case AskingEmail = 'asking_email';
}

// 2. Use it
$chat->state()->transitionTo(ChatState::AskingName);

// 3. Check
if ($chat->state()->is(ChatState::AskingName)) {
    // ask for name
}

// 4. Store data alongside state
$chat->state()->transitionTo(ChatState::AskingEmail, [
    'name' => 'John',
    'attempts' => 1
]);

// 5. Read data
$name = $chat->state()->get('name');
```

## Level 3: State Machine (Strict Rules)
Perfect for: orders, payments, workflows

```php
// 1. Create enum with rules
enum OrderState: string {
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';

    public function canTransitionTo(self $target): bool {
        return match($this) {
            self::Pending => $target === self::Paid,
            self::Paid => $target === self::Shipped,
            self::Shipped => false,
        };
    }
}

// 2. Use it
$order->state()->transitionTo(OrderState::Pending);
$order->state()->transitionTo(OrderState::Paid); // ✓ OK

try {
    $order->state()->transitionTo(OrderState::Pending); // ✗ Exception
} catch (InvalidTransitionException $e) {
    // Handle invalid transition
}
```

## Bonus: Flow Handlers (Side Effects)

```php
use Modules\Toolbox\StateMachine\Contracts\FlowHandler;

class OrderFlow implements FlowHandler {
    public function onEnter(UnitEnum $state, array $data, Model $order): void {
        match($state) {
            OrderState::Paid => Mail::to($order->user)->send(new PaymentConfirmed($order)),
            OrderState::Shipped => Notification::send($order->user, new OrderShipped($order)),
            default => null,
        };
    }

    public function onExit(UnitEnum $state, array $data, Model $order): void {
        // Optional cleanup
    }
}

// Use with flow
$order->state()
    ->useFlow(new OrderFlow())
    ->transitionTo(OrderState::Paid); // Sends email automatically
```

## Common Patterns

### Pattern 1: Multi-step Form
```php
enum RegistrationState: string {
    case Step1 = 'step_1';
    case Step2 = 'step_2';
    case Step3 = 'step_3';
    case Complete = 'complete';
}

// Controller
public function step1(Request $request) {
    $user->state()->transitionTo(RegistrationState::Step1);
    $user->state()->set('form_data.step1', $request->validated());
}

public function step2(Request $request) {
    $user->state()->transitionTo(RegistrationState::Step2);
    $user->state()->set('form_data.step2', $request->validated());
}
```

### Pattern 2: Quiz/Learning
```php
// No enum needed, just storage
$attempt->state()->set('questions_answered', [1, 2, 3]);
$attempt->state()->set('current_question', 4);
$attempt->state()->set('score', 75);
$attempt->state()->set('started_at', now());

// Check progress
$progress = $attempt->state()->get('questions_answered', []);
$score = $attempt->state()->get('score', 0);
```

### Pattern 3: Telegram Bot
```php
enum BotState: string {
    case Idle = 'idle';
    case AskingLanguage = 'asking_language';
    case Translating = 'translating';
}

// On /start
$chat->state()->transitionTo(BotState::AskingLanguage);

// On message
$currentState = $chat->state()->current();
match($currentState) {
    BotState::AskingLanguage => $this->handleLanguageInput($message),
    BotState::Translating => $this->translateText($message),
    default => $this->showHelp(),
};

// Store context
$chat->state()->set('target_language', 'en');
$chat->state()->set('history', ['hello', 'world']);
```

## Events

Listen to state changes:

```php
// In EventServiceProvider or listener
Event::listen(StateChanged::class, function($event) {
    Log::info("State changed from {$event->fromState?->name} to {$event->toState->name}");
});

Event::listen(TransitionFailed::class, function($event) {
    Log::error("Transition failed: {$event->exception->getMessage()}");
});
```

## Tips

1. **Don't mix levels** - Pick one approach per model
2. **Enum for states, storage for data** - Use `transitionTo()` for state, `set()` for related data
3. **Add helper methods to enums** - `label()`, `color()`, `icon()`, etc.
4. **Use FlowHandler for notifications** - Keep business logic in handlers
5. **Test transition rules** - Write tests for `canTransitionTo()` logic

## See Also

- [README.md](README.md) - Full documentation
- [Examples/](Examples/) - Complete examples
- Tests: `tests/Feature/StateMachine/StateManagerTest.php`
