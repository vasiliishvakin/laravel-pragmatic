<?php

declare(strict_types=1);

use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\CommandBus;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Operation;
use Pragmatic\Cqrs\Query;
use Pragmatic\Cqrs\QueryBus;

beforeEach(function () {
    config(['pragmatic.cqrs.query_middleware' => []]);
    config(['pragmatic.cqrs.command_middleware' => []]);
});

test('withoutMiddleware excludes specific middleware from runtime execution', function () {
    $executed = [];

    $middleware1 = new class($executed, '1') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $middleware2 = new class($executed, '2') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

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
        $query->withMiddleware([$middleware1, $middleware2])
            ->withoutMiddleware([get_class($middleware2)])
    );

    expect($executed)->toBe(['1']); // Only middleware1 executed
});

test('excludeMiddleware method excludes middleware at class level', function () {
    $executed = [];

    $middleware1 = new class($executed, '1') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $middleware2 = new class($executed, '2') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $query = new class($middleware1, $middleware2) extends Query
    {
        public function __construct(
            private object $middleware1,
            private object $middleware2,
        ) {}

        public function middleware(): array
        {
            return [$this->middleware1, $this->middleware2];
        }

        public function excludeMiddleware(): array
        {
            return [get_class($this->middleware2)];
        }

        public function execute(): string
        {
            return 'result';
        }
    };

    app(QueryBus::class)->execute($query);

    expect($executed)->toBe(['1']); // Only middleware1 executed
});

test('withoutMiddleware can exclude global middleware', function () {
    $executed = [];

    $globalMiddleware = new class($executed, 'global') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    config(['pragmatic.cqrs.query_middleware' => [$globalMiddleware]]);

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withoutMiddleware([get_class($globalMiddleware)])
    );

    expect($executed)->toBe([]); // Global middleware excluded
});

test('withoutMiddleware can exclude per-class middleware', function () {
    $executed = [];

    $classMiddleware = new class($executed, 'class') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $query = new class($classMiddleware) extends Query
    {
        public function __construct(private object $middleware) {}

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
        $query->withoutMiddleware([get_class($classMiddleware)])
    );

    expect($executed)->toBe([]); // Class middleware excluded
});

test('excludeMiddleware and withoutMiddleware work together', function () {
    $executed = [];

    $middleware1 = new class($executed, '1') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $middleware2 = new class($executed, '2') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $middleware3 = new class($executed, '3') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $query = new class($middleware1, $middleware2, $middleware3) extends Query
    {
        public function __construct(
            private object $m1,
            private object $m2,
            private object $m3,
        ) {}

        public function middleware(): array
        {
            return [$this->m1, $this->m2];
        }

        public function excludeMiddleware(): array
        {
            return [get_class($this->m1)]; // Exclude at class level
        }

        public function execute(): string
        {
            return 'result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([$middleware3])
            ->withoutMiddleware([get_class($middleware3)]) // Exclude at runtime
    );

    expect($executed)->toBe(['2']); // Only middleware2 executed
});

test('withoutMiddleware works with command bus', function () {
    $executed = [];

    $middleware = new class($executed) implements Middleware
    {
        public function __construct(private array &$executed) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = 'executed';

            return $next($operation);
        }
    };

    $command = new class extends Command
    {
        public function execute(): string
        {
            return 'done';
        }
    };

    app(CommandBus::class)->execute(
        $command->withMiddleware([$middleware])
            ->withoutMiddleware([get_class($middleware)])
    );

    expect($executed)->toBe([]); // Middleware excluded
});

test('withoutMiddleware accepts single middleware class as string', function () {
    $executed = [];

    $middleware = new class($executed) implements Middleware
    {
        public function __construct(private array &$executed) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = 'executed';

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
        $query->withMiddleware([$middleware])
            ->withoutMiddleware(get_class($middleware)) // String instead of array
    );

    expect($executed)->toBe([]); // Middleware excluded
});

test('withoutMiddleware can be chained multiple times', function () {
    $executed = [];

    $middleware1 = new class($executed, '1') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $middleware2 = new class($executed, '2') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

            return $next($operation);
        }
    };

    $middleware3 = new class($executed, '3') implements Middleware
    {
        public function __construct(private array &$executed, private string $id) {}

        public function handle(Operation $operation, Closure $next): mixed
        {
            $this->executed[] = $this->id;

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
        $query->withMiddleware([$middleware1, $middleware2, $middleware3])
            ->withoutMiddleware([get_class($middleware1)])
            ->withoutMiddleware([get_class($middleware3)])
    );

    expect($executed)->toBe(['2']); // Only middleware2 executed
});
