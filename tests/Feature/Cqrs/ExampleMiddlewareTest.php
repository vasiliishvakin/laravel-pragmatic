<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\CommandBus;
use Pragmatic\Cqrs\Middleware\CachingMiddleware;
use Pragmatic\Cqrs\Middleware\LoggingMiddleware;
use Pragmatic\Cqrs\Middleware\TransactionMiddleware;
use Pragmatic\Cqrs\Middleware\ValidationMiddleware;
use Pragmatic\Cqrs\Query;
use Pragmatic\Cqrs\QueryBus;

test('LoggingMiddleware logs query execution', function () {
    Log::shouldReceive('info')
        ->once()
        ->with(\Mockery::pattern('/Executing Query:/'));

    Log::shouldReceive('info')
        ->once()
        ->with(\Mockery::pattern('/Completed Query:/'));

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([LoggingMiddleware::class])
    );
});

test('LoggingMiddleware logs errors on exception', function () {
    Log::shouldReceive('info')
        ->once()
        ->with(\Mockery::pattern('/Executing Command:/'));

    Log::shouldReceive('error')
        ->once()
        ->with(
            \Mockery::pattern('/Failed Command:/'),
            \Mockery::type('array')
        );

    $command = new class extends Command
    {
        public function execute(): mixed
        {
            throw new RuntimeException('Test error');
        }
    };

    expect(fn () => app(CommandBus::class)->execute(
        $command->withMiddleware([LoggingMiddleware::class])
    ))->toThrow(RuntimeException::class);
});

test('TransactionMiddleware wraps command in transaction', function () {
    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(fn ($callback) => $callback());

    $command = new class extends Command
    {
        public function execute(): string
        {
            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute(
        $command->withMiddleware([TransactionMiddleware::class])
    );

    expect($result)->toBe('done');
});

test('ValidationMiddleware calls validate method if present', function () {
    $validated = false;

    $command = new class($validated) extends Command
    {
        public function __construct(private bool &$validated) {}

        public function validate(): void
        {
            $this->validated = true;
        }

        public function execute(): string
        {
            return 'done';
        }
    };

    app(CommandBus::class)->execute(
        $command->withMiddleware([ValidationMiddleware::class])
    );

    expect($validated)->toBeTrue();
});

test('ValidationMiddleware short-circuits on validation failure', function () {
    $executed = false;

    $command = new class($executed) extends Command
    {
        public function __construct(private bool &$executed) {}

        public function validate(): void
        {
            throw ValidationException::withMessages([
                'field' => ['Validation failed'],
            ]);
        }

        public function execute(): mixed
        {
            $this->executed = true;
            return null;
        }
    };

    expect(fn () => app(CommandBus::class)->execute(
        $command->withMiddleware([ValidationMiddleware::class])
    ))->toThrow(ValidationException::class)
        ->and($executed)->toBeFalse();
});

test('ValidationMiddleware skips validation if no validate method', function () {
    $command = new class extends Command
    {
        public function execute(): string
        {
            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute(
        $command->withMiddleware([ValidationMiddleware::class])
    );

    expect($result)->toBe('done');
});

test('CachingMiddleware caches query results', function () {
    $executions = 0;

    $query = new class($executions) extends Query
    {
        public function __construct(private int &$executions) {}

        public function cacheKey(): string
        {
            return 'test-cache-key';
        }

        public function execute(): string
        {
            $this->executions++;

            return 'result';
        }
    };

    // First execution - should execute and cache
    $result1 = app(QueryBus::class)->execute(
        $query->withMiddleware([CachingMiddleware::class])
    );

    // Second execution - should return from cache
    $result2 = app(QueryBus::class)->execute(
        $query->withMiddleware([CachingMiddleware::class])
    );

    expect($result1)->toBe('result')
        ->and($result2)->toBe('result')
        ->and($executions)->toBe(1); // Only executed once

    // Cleanup
    Cache::forget('test-cache-key');
});

test('CachingMiddleware respects custom TTL', function () {
    $executions = 0;

    $query = new class($executions) extends Query
    {
        public function __construct(private int &$executions) {}

        public function cacheKey(): string
        {
            return 'test-custom-ttl-key';
        }

        public function cacheTtl(): int
        {
            return 7200; // 2 hours
        }

        public function execute(): string
        {
            $this->executions++;

            return 'result-with-ttl';
        }
    };

    // First execution - should execute and cache
    $result1 = app(QueryBus::class)->execute(
        $query->withMiddleware([CachingMiddleware::class])
    );

    // Second execution - should return from cache
    $result2 = app(QueryBus::class)->execute(
        $query->withMiddleware([CachingMiddleware::class])
    );

    expect($result1)->toBe('result-with-ttl')
        ->and($result2)->toBe('result-with-ttl')
        ->and($executions)->toBe(1); // Only executed once due to caching

    // Cleanup
    Cache::forget('test-custom-ttl-key');
});

test('CachingMiddleware skips caching for commands', function () {
    $executions = 0;

    $command = new class($executions) extends Command
    {
        public function __construct(private int &$executions) {}

        public function cacheKey(): string
        {
            return 'should-not-cache';
        }

        public function execute(): string
        {
            $this->executions++;

            return 'done';
        }
    };

    // Execute twice
    app(CommandBus::class)->execute(
        $command->withMiddleware([CachingMiddleware::class])
    );
    app(CommandBus::class)->execute(
        $command->withMiddleware([CachingMiddleware::class])
    );

    // Should execute both times (not cached)
    expect($executions)->toBe(2);
});

test('CachingMiddleware skips caching if no cacheKey method', function () {
    $executions = 0;

    $query = new class($executions) extends Query
    {
        public function __construct(private int &$executions) {}

        public function execute(): string
        {
            $this->executions++;

            return 'result';
        }
    };

    // Execute twice
    app(QueryBus::class)->execute(
        $query->withMiddleware([CachingMiddleware::class])
    );
    app(QueryBus::class)->execute(
        $query->withMiddleware([CachingMiddleware::class])
    );

    // Should execute both times (not cached)
    expect($executions)->toBe(2);
});
