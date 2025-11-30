<?php

declare(strict_types=1);

use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\CommandBus;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Operation;
use Pragmatic\Cqrs\Query;
use Pragmatic\Cqrs\QueryBus;

beforeEach(function () {
    // Reset config before each test
    config(['pragmatic.cqrs.query_middleware' => []]);
    config(['pragmatic.cqrs.command_middleware' => []]);
});

test('query executes without middleware when none defined', function () {
    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    $result = app(QueryBus::class)->execute($query);

    expect($result)->toBe('result');
});

test('command executes without middleware when none defined', function () {
    $command = new class extends Command
    {
        public function execute(): string
        {
            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute($command);

    expect($result)->toBe('done');
});

test('query executes with runtime middleware', function () {
    $executed = [];

    $middleware = new class($executed) implements Middleware
    {
        public function __construct(private array &$executed) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = 'before';
            $result = $next($operation);
            $this->executed[] = 'after';

            return $result;
        }
    };

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    $result = app(QueryBus::class)->execute(
        $query->withMiddleware([$middleware])
    );

    expect($result)->toBe('result')
        ->and($executed)->toBe(['before', 'after']);
});

test('middleware executes in correct order: global -> class -> runtime', function () {
    $order = [];

    $globalMiddleware = new class($order, 'global') implements Middleware
    {
        public function __construct(private array &$order, private string $name) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->order[] = $this->name.':before';
            $result = $next($operation);
            $this->order[] = $this->name.':after';

            return $result;
        }
    };

    $classMiddleware = new class($order, 'class') implements Middleware
    {
        public function __construct(private array &$order, private string $name) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->order[] = $this->name.':before';
            $result = $next($operation);
            $this->order[] = $this->name.':after';

            return $result;
        }
    };

    $runtimeMiddleware = new class($order, 'runtime') implements Middleware
    {
        public function __construct(private array &$order, private string $name) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->order[] = $this->name.':before';
            $result = $next($operation);
            $this->order[] = $this->name.':after';

            return $result;
        }
    };

    config(['pragmatic.cqrs.query_middleware' => [$globalMiddleware]]);

    $query = new class($classMiddleware) extends Query
    {
        public function __construct(private Middleware $middleware) {}

        public function middleware(): array
        {
            return [$this->middleware];
        }

        public function execute(): string
        {
            return 'result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([$runtimeMiddleware])
    );

    expect($order)->toBe([
        'global:before',
        'class:before',
        'runtime:before',
        'runtime:after',
        'class:after',
        'global:after',
    ]);
});

test('middleware can transform result', function () {
    $middleware = new class implements Middleware
    {
        public function handle(Operation $operation, Closure $next): mixed
        {
            $result = $next($operation);

            return strtoupper($result);
        }
    };

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    $result = app(QueryBus::class)->execute(
        $query->withMiddleware([$middleware])
    );

    expect($result)->toBe('RESULT');
});

test('middleware can short-circuit execution', function () {
    $executed = false;

    $middleware = new class implements Middleware
    {
        public function handle(Operation $operation, Closure $next): mixed
        {
            // Short-circuit: return without calling $next
            return 'short-circuited';
        }
    };

    $query = new class($executed) extends Query
    {
        public function __construct(private bool &$executed) {}

        public function execute(): string
        {
            $this->executed = true;

            return 'should not execute';
        }
    };

    $result = app(QueryBus::class)->execute(
        $query->withMiddleware([$middleware])
    );

    expect($result)->toBe('short-circuited')
        ->and($executed)->toBeFalse();
});

test('exception in middleware stops pipeline', function () {
    $afterExecuted = false;

    $middleware1 = new class implements Middleware
    {
        public function handle(Operation $operation, Closure $next): mixed
        {
            throw new RuntimeException('Middleware error');
        }
    };

    $middleware2 = new class($afterExecuted) implements Middleware
    {
        public function __construct(private bool &$afterExecuted) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->afterExecuted = true;

            return $next($operation);
        }
    };

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    expect(fn () => app(QueryBus::class)->execute(
        $query->withMiddleware([$middleware1, $middleware2])
    ))->toThrow(RuntimeException::class, 'Middleware error')
        ->and($afterExecuted)->toBeFalse();
});

test('command middleware works same as query middleware', function () {
    $order = [];

    $middleware = new class($order) implements Middleware
    {
        public function __construct(private array &$order) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->order[] = 'before';
            $result = $next($operation);
            $this->order[] = 'after';

            return $result;
        }
    };

    $command = new class extends Command
    {
        public function execute(): string
        {
            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute(
        $command->withMiddleware([$middleware])
    );

    expect($result)->toBe('done')
        ->and($order)->toBe(['before', 'after']);
});

test('multiple runtime middleware can be chained with withMiddleware', function () {
    $order = [];

    $middleware1 = new class($order, '1') implements Middleware
    {
        public function __construct(private array &$order, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->order[] = $this->id;

            return $next($operation);
        }
    };

    $middleware2 = new class($order, '2') implements Middleware
    {
        public function __construct(private array &$order, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->order[] = $this->id;

            return $next($operation);
        }
    };

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([$middleware1])
            ->withMiddleware([$middleware2])
    );

    expect($order)->toBe(['1', '2']);
});
