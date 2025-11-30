<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\CommandBus;
use Pragmatic\Cqrs\Events\CommandExecuted;
use Pragmatic\Cqrs\Events\CommandExecuting;
use Pragmatic\Cqrs\Events\CommandFailed;
use Pragmatic\Cqrs\Events\QueryExecuted;
use Pragmatic\Cqrs\Events\QueryExecuting;
use Pragmatic\Cqrs\Events\QueryFailed;
use Pragmatic\Cqrs\Middleware\EventMiddleware;
use Pragmatic\Cqrs\Query;
use Pragmatic\Cqrs\QueryBus;

test('EventMiddleware dispatches QueryExecuting before query execution', function () {
    Event::fake([QueryExecuting::class]);

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([EventMiddleware::class])
    );

    Event::assertDispatched(QueryExecuting::class, function ($event) use ($query) {
        return $event->query === $query;
    });
});

test('EventMiddleware dispatches QueryExecuted after successful query execution', function () {
    Event::fake([QueryExecuted::class]);

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'test-result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([EventMiddleware::class])
    );

    Event::assertDispatched(QueryExecuted::class, function ($event) use ($query) {
        return $event->query === $query
            && $event->result === 'test-result'
            && $event->executionTime > 0;
    });
});

test('EventMiddleware dispatches QueryFailed when query throws exception', function () {
    Event::fake([QueryFailed::class]);

    $query = new class extends Query
    {
        public function execute(): mixed
        {
            throw new RuntimeException('Test error');
        }
    };

    try {
        app(QueryBus::class)->execute(
            $query->withMiddleware([EventMiddleware::class])
        );
    } catch (RuntimeException $e) {
        // Expected
    }

    Event::assertDispatched(QueryFailed::class, function ($event) use ($query) {
        return $event->query === $query
            && $event->exception instanceof RuntimeException
            && $event->exception->getMessage() === 'Test error'
            && $event->executionTime > 0;
    });
});

test('EventMiddleware dispatches CommandExecuting before command execution', function () {
    Event::fake([CommandExecuting::class]);

    $command = new class extends Command
    {
        public function execute(): string
        {
            return 'done';
        }
    };

    app(CommandBus::class)->execute(
        $command->withMiddleware([EventMiddleware::class])
    );

    Event::assertDispatched(CommandExecuting::class, function ($event) use ($command) {
        return $event->command === $command;
    });
});

test('EventMiddleware dispatches CommandExecuted after successful command execution', function () {
    Event::fake([CommandExecuted::class]);

    $command = new class extends Command
    {
        public function execute(): string
        {
            return 'command-result';
        }
    };

    app(CommandBus::class)->execute(
        $command->withMiddleware([EventMiddleware::class])
    );

    Event::assertDispatched(CommandExecuted::class, function ($event) use ($command) {
        return $event->command === $command
            && $event->result === 'command-result'
            && $event->executionTime > 0;
    });
});

test('EventMiddleware dispatches CommandFailed when command throws exception', function () {
    Event::fake([CommandFailed::class]);

    $command = new class extends Command
    {
        public function execute(): mixed
        {
            throw new RuntimeException('Command error');
        }
    };

    try {
        app(CommandBus::class)->execute(
            $command->withMiddleware([EventMiddleware::class])
        );
    } catch (RuntimeException $e) {
        // Expected
    }

    Event::assertDispatched(CommandFailed::class, function ($event) use ($command) {
        return $event->command === $command
            && $event->exception instanceof RuntimeException
            && $event->exception->getMessage() === 'Command error'
            && $event->executionTime > 0;
    });
});

test('EventMiddleware dispatches all lifecycle events in correct order', function () {
    $events = [];

    Event::listen(QueryExecuting::class, function () use (&$events) {
        $events[] = 'executing';
    });

    Event::listen(QueryExecuted::class, function () use (&$events) {
        $events[] = 'executed';
    });

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([EventMiddleware::class])
    );

    expect($events)->toBe(['executing', 'executed']);
});

test('EventMiddleware can be used with event listeners', function () {
    $loggedQueries = [];

    Event::listen(QueryExecuted::class, function (QueryExecuted $event) use (&$loggedQueries) {
        $loggedQueries[] = [
            'class' => get_class($event->query),
            'result' => $event->result,
            'time' => $event->executionTime,
        ];
    });

    $query = new class extends Query
    {
        public function execute(): string
        {
            return 'logged-result';
        }
    };

    app(QueryBus::class)->execute(
        $query->withMiddleware([EventMiddleware::class])
    );

    expect($loggedQueries)->toHaveCount(1)
        ->and($loggedQueries[0]['result'])->toBe('logged-result')
        ->and($loggedQueries[0]['time'])->toBeGreaterThan(0);
});
