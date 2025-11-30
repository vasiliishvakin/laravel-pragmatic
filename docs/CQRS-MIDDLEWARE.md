# CQRS Middleware

The Laravel Pragmatic Toolkit provides a powerful middleware system for Query and Command buses, following Laravel's familiar Pipeline pattern.

## Overview

Middleware allows you to:
- **Inspect** operations before execution
- **Modify** operations or their results
- **Short-circuit** execution (prevent execution)
- **Transform** results after execution
- **Wrap** execution (transactions, logging, etc.)

## Middleware Execution Order

Middleware executes in three layers, from outer to inner:

1. **Global middleware** (from config)
2. **Per-class middleware** (from `middleware()` method)
3. **Runtime middleware** (from `withMiddleware()`)

```
Global Middleware (outer)
  ↓
Per-Class Middleware
  ↓
Runtime Middleware (inner)
  ↓
boot() → execute()
  ↓
Runtime Middleware (after)
  ↓
Per-Class Middleware (after)
  ↓
Global Middleware (after)
```

## Creating Middleware

Implement the `Pragmatic\Cqrs\Contracts\Middleware` interface:

```php
use Closure;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Query;

class MyMiddleware implements Middleware
{
    public function handle(Query|Command $operation, Closure $next): mixed
    {
        // Before execution

        $result = $next($operation);

        // After execution

        return $result;
    }
}
```

### Dependency Injection

Middleware supports constructor dependency injection:

```php
class LoggingMiddleware implements Middleware
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(Query|Command $operation, Closure $next): mixed
    {
        $this->logger->info('Executing: ' . get_class($operation));

        return $next($operation);
    }
}
```

## Registering Middleware

### 1. Global Middleware (Config)

Add to `config/pragmatic.php`:

```php
'cqrs' => [
    'query_middleware' => [
        \Pragmatic\Cqrs\Middleware\LoggingMiddleware::class,
    ],
    'command_middleware' => [
        \Pragmatic\Cqrs\Middleware\TransactionMiddleware::class,
        \Pragmatic\Cqrs\Middleware\LoggingMiddleware::class,
    ],
],
```

### 2. Per-Class Middleware

Define in your Query/Command class:

```php
class GetUserQuery extends Query
{
    public function middleware(): array
    {
        return [
            CachingMiddleware::class,
            LoggingMiddleware::class,
        ];
    }

    public function execute(): User
    {
        return User::find($this->userId);
    }
}
```

### 3. Runtime Middleware (Fluent)

Add when creating the operation:

```php
use Pragmatic\Cqrs\QueryBus;

$user = QueryBus::execute(
    GetUserQuery::make(userId: 1)
        ->withMiddleware([AuthorizationMiddleware::class])
);
```

You can chain multiple runtime middleware:

```php
QueryBus::execute(
    GetUserQuery::make(userId: 1)
        ->withMiddleware([AuthorizationMiddleware::class])
        ->withMiddleware([RateLimitMiddleware::class])
);
```

## Middleware Capabilities

### Before Execution

Execute logic before the operation runs:

```php
public function handle(Query|Command $operation, Closure $next): mixed
{
    // Validate
    if (!$this->isValid($operation)) {
        throw new ValidationException('Invalid operation');
    }

    // Log
    $this->logger->info('Starting...');

    return $next($operation);
}
```

### After Execution

Execute logic after the operation runs:

```php
public function handle(Query|Command $operation, Closure $next): mixed
{
    $result = $next($operation);

    // Log result
    $this->logger->info('Completed', ['result' => $result]);

    // Cache result
    $this->cache->put($operation->cacheKey(), $result);

    return $result;
}
```

### Transform Results

Modify the operation result:

```php
public function handle(Query|Command $operation, Closure $next): mixed
{
    $result = $next($operation);

    // Transform result
    return new UserResource($result);
}
```

### Short-Circuit Execution

Return a result without executing the operation:

```php
public function handle(Query|Command $operation, Closure $next): mixed
{
    // Check cache
    if ($cached = $this->cache->get($operation->cacheKey())) {
        return $cached; // Return without calling $next
    }

    $result = $next($operation);
    $this->cache->put($operation->cacheKey(), $result);

    return $result;
}
```

### Wrap Execution

Wrap the operation in a transaction, try-catch, etc.:

```php
public function handle(Query|Command $operation, Closure $next): mixed
{
    return DB::transaction(function () use ($operation, $next) {
        return $next($operation);
    });
}
```

## Built-in Middleware

### LoggingMiddleware

Logs operation execution with automatic error handling:

```php
use Pragmatic\Cqrs\Middleware\LoggingMiddleware;

class GetUserQuery extends Query
{
    public function middleware(): array
    {
        return [LoggingMiddleware::class];
    }
}
```

### TransactionMiddleware

Wraps command execution in a database transaction:

```php
use Pragmatic\Cqrs\Middleware\TransactionMiddleware;

class CreateOrderCommand extends Command
{
    public function middleware(): array
    {
        return [TransactionMiddleware::class];
    }
}
```

### ValidationMiddleware

Validates operations before execution. Add a `validate()` method to your operation:

```php
use Pragmatic\Cqrs\Middleware\ValidationMiddleware;
use Illuminate\Validation\ValidationException;

class CreateUserCommand extends Command
{
    public function middleware(): array
    {
        return [ValidationMiddleware::class];
    }

    public function validate(): void
    {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email format'],
            ]);
        }
    }

    public function execute(): User
    {
        // Only executes if validation passes
    }
}
```

### CachingMiddleware

Caches query results. Add `cacheKey()` and optionally `cacheTtl()` methods:

```php
use Pragmatic\Cqrs\Middleware\CachingMiddleware;

class GetUserQuery extends Query
{
    public function middleware(): array
    {
        return [CachingMiddleware::class];
    }

    public function cacheKey(): string
    {
        return "user:{$this->userId}";
    }

    // Optional: default is 3600 seconds (1 hour)
    public function cacheTtl(): int
    {
        return 7200; // 2 hours
    }

    public function execute(): User
    {
        return User::find($this->userId);
    }
}
```

## Exception Handling

Exceptions in middleware stop the pipeline:

```php
public function handle(Query|Command $operation, Closure $next): mixed
{
    try {
        return $next($operation);
    } catch (SomeException $e) {
        // Handle or transform exception
        $this->logger->error('Failed', ['error' => $e]);
        throw new CustomException('Operation failed', 0, $e);
    }
}
```

## Best Practices

1. **Keep middleware focused** - One responsibility per middleware
2. **Use global middleware sparingly** - Only for truly universal concerns
3. **Prefer per-class middleware** - More explicit and maintainable
4. **Use runtime middleware for special cases** - Authorization, rate limiting
5. **Order matters** - Outer middleware wraps inner middleware
6. **Don't abuse short-circuiting** - Use sparingly, document clearly
7. **Handle exceptions gracefully** - Log and re-throw or transform

## Example: Complete Workflow

```php
// config/pragmatic.php
'cqrs' => [
    'command_middleware' => [
        LoggingMiddleware::class,  // Logs all commands
    ],
],

// Command with per-class middleware
class CreateOrderCommand extends Command
{
    public function __construct(
        private readonly int $userId,
        private readonly array $items,
    ) {}

    public function middleware(): array
    {
        return [
            ValidationMiddleware::class,    // Validates before execution
            TransactionMiddleware::class,   // Wraps in DB transaction
        ];
    }

    public function validate(): void
    {
        if (empty($this->items)) {
            throw ValidationException::withMessages([
                'items' => ['Order must have at least one item'],
            ]);
        }
    }

    public function execute(): Order
    {
        return Order::create([
            'user_id' => $this->userId,
            'items' => $this->items,
        ]);
    }
}

// Usage with runtime middleware
use Pragmatic\Cqrs\CommandBus;

$order = CommandBus::execute(
    CreateOrderCommand::make(userId: 1, items: [...])
        ->withMiddleware([RateLimitMiddleware::class])
);
```

**Execution order:**
1. LoggingMiddleware (global) - before
2. ValidationMiddleware (per-class) - before
3. TransactionMiddleware (per-class) - before
4. RateLimitMiddleware (runtime) - before
5. **boot() → execute()**
6. RateLimitMiddleware - after
7. TransactionMiddleware - after
8. ValidationMiddleware - after
9. LoggingMiddleware - after

## Performance Notes

- **No middleware = no overhead** - Buses skip pipeline when no middleware is registered
- **Container resolution** - Middleware classes are resolved from the service container
- **Singleton by default** - Middleware instances are cached by Laravel's container
- **Minimal overhead** - Pipeline uses closures, very efficient

## Testing Middleware

See `tests/Feature/Cqrs/MiddlewareTest.php` and `tests/Feature/Cqrs/ExampleMiddlewareTest.php` for comprehensive test examples.

```php
use Pragmatic\Cqrs\QueryBus;

test('middleware executes in correct order', function () {
    $order = [];

    $middleware = new class($order) implements Middleware {
        public function __construct(private array &$order) {}

        public function handle(Query|Command $operation, Closure $next): mixed {
            $this->order[] = 'before';
            $result = $next($operation);
            $this->order[] = 'after';
            return $result;
        }
    };

    $query = new class extends Query {
        public function execute(): string {
            return 'result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([$middleware])
    );

    expect($order)->toBe(['before', 'after']);
});
```

## EventMiddleware - Lifecycle Events

The `EventMiddleware` dispatches Laravel events at different stages of Query/Command execution, allowing you to listen and react to lifecycle events.

### Available Events

**Query Events:**
- `Pragmatic\Cqrs\Events\QueryExecuting` - Before query execution
- `Pragmatic\Cqrs\Events\QueryExecuted` - After successful execution
- `Pragmatic\Cqrs\Events\QueryFailed` - When execution fails

**Command Events:**
- `Pragmatic\Cqrs\Events\CommandExecuting` - Before command execution
- `Pragmatic\Cqrs\Events\CommandExecuted` - After successful execution
- `Pragmatic\Cqrs\Events\CommandFailed` - When execution fails

### Event Properties

**Executing Events** (`QueryExecuting`, `CommandExecuting`):
```php
public readonly Query|Command $query|$command;
```

**Executed Events** (`QueryExecuted`, `CommandExecuted`):
```php
public readonly Query|Command $query|$command;
public readonly mixed $result;              // Operation result
public readonly float $executionTime;       // Execution time in seconds
```

**Failed Events** (`QueryFailed`, `CommandFailed`):
```php
public readonly Query|Command $query|$command;
public readonly Throwable $exception;       // The thrown exception
public readonly float $executionTime;       // Time until failure
```

### Usage Example

```php
// 1. Enable EventMiddleware globally (config/pragmatic.php)
'cqrs' => [
    'query_middleware' => [
        \Pragmatic\Cqrs\Middleware\EventMiddleware::class,
    ],
    'command_middleware' => [
        \Pragmatic\Cqrs\Middleware\EventMiddleware::class,
    ],
],

// 2. Create event listeners
use Pragmatic\Cqrs\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
    Log::info('Query executed', [
        'query' => get_class($event->query),
        'execution_time' => $event->executionTime,
    ]);
});

// 3. Listen to failures for monitoring
use Pragmatic\Cqrs\Events\CommandFailed;

Event::listen(CommandFailed::class, function (CommandFailed $event) {
    report($event->exception);

    Log::error('Command failed', [
        'command' => get_class($event->command),
        'error' => $event->exception->getMessage(),
        'time' => $event->executionTime,
    ]);
});
```

### Practical Use Cases

**Performance Monitoring:**
```php
Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
    if ($event->executionTime > 1.0) {
        Log::warning('Slow query detected', [
            'query' => get_class($event->query),
            'time' => $event->executionTime,
        ]);
    }
});
```

**Auditing:**
```php
Event::listen(CommandExecuted::class, function (CommandExecuted $event) {
    AuditLog::create([
        'action' => get_class($event->command),
        'user_id' => auth()->id(),
        'executed_at' => now(),
    ]);
});
```

**Cache Invalidation:**
```php
Event::listen(CommandExecuted::class, function (CommandExecuted $event) {
    if ($event->command instanceof CreateOrderCommand) {
        Cache::tags(['orders'])->flush();
    }
});
```

## Excluding Middleware

You can exclude specific middleware from execution at two levels: **class-level** and **runtime**.

### Class-Level Exclusion

Override the `excludeMiddleware()` method in your Query/Command:

```php
use Pragmatic\Cqrs\Query;
use Pragmatic\Cqrs\Middleware\EventMiddleware;

class GetUserQuery extends Query
{
    // Exclude EventMiddleware for this query type
    public function excludeMiddleware(): array
    {
        return [EventMiddleware::class];
    }

    public function execute(): User
    {
        return User::find($this->userId);
    }
}
```

### Runtime Exclusion

Use the `withoutMiddleware()` fluent method:

```php
use Pragmatic\Cqrs\QueryBus;
use Pragmatic\Cqrs\Middleware\LoggingMiddleware;

// Exclude specific middleware for this execution
$user = QueryBus::execute(
    GetUserQuery::make(userId: 1)
        ->withoutMiddleware([LoggingMiddleware::class])
);

// Exclude multiple middleware
$user = QueryBus::execute(
    GetUserQuery::make(userId: 1)
        ->withoutMiddleware([
            EventMiddleware::class,
            LoggingMiddleware::class,
        ])
);

// Single middleware (without array)
$user = QueryBus::execute(
    GetUserQuery::make(userId: 1)
        ->withoutMiddleware(EventMiddleware::class)
);
```

### Chaining Exclusions

```php
$user = QueryBus::execute(
    GetUserQuery::make(userId: 1)
        ->withMiddleware([CustomMiddleware::class])
        ->withoutMiddleware(EventMiddleware::class)
        ->withoutMiddleware(LoggingMiddleware::class)
);
```

### Exclusion Priority

Middleware is excluded if it appears in **either** `excludeMiddleware()` or `withoutMiddleware()`:

```php
class GetUserQuery extends Query
{
    // Class-level exclusion
    public function excludeMiddleware(): array
    {
        return [EventMiddleware::class];
    }
}

// Runtime exclusion adds to class-level
QueryBus::execute(
    GetUserQuery::make(userId: 1)
        ->withoutMiddleware([LoggingMiddleware::class])
);
// Both EventMiddleware AND LoggingMiddleware are excluded
```

### Use Cases for Exclusion

**Disable events for bulk operations:**
```php
// Avoid event spam during imports
foreach ($users as $userData) {
    CommandBus::execute(
        CreateUserCommand::make($userData)
            ->withoutMiddleware([EventMiddleware::class])
    );
}
```

**Skip logging for health checks:**
```php
class HealthCheckQuery extends Query
{
    public function excludeMiddleware(): array
    {
        return [
            LoggingMiddleware::class,
            EventMiddleware::class,
        ];
    }
}
```

**Disable transactions for read-only commands:**
```php
CommandBus::execute(
    ReportGenerationCommand::make()
        ->withoutMiddleware([TransactionMiddleware::class])
);
```
