<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Gate;
use Pragmatic\Cqrs\Command;
use Pragmatic\Cqrs\CommandBus;
use Pragmatic\Cqrs\Middleware\AuthorizationMiddleware;
use Pragmatic\Cqrs\Query;
use Pragmatic\Cqrs\QueryBus;

// Test User model
class TestUser extends Authenticatable
{
    protected $fillable = ['id', 'name', 'email'];

    protected $guarded = [];
}

beforeEach(function () {
    // Reset middleware config
    config(['pragmatic.cqrs.query_middleware' => []]);
    config(['pragmatic.cqrs.command_middleware' => []]);
});

test('AuthorizationMiddleware allows operation when authorize() returns true', function () {
    $user = new TestUser(['id' => 1]);
    $this->actingAs($user);

    $executed = false;

    $command = new class($executed) extends Command
    {
        public function __construct(private bool &$executed) {}

        public function authorize(GateContract $gate): bool
        {
            return true;
        }

        public function execute(): mixed
        {
            $this->executed = true;

            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute(
        $command->withMiddleware([AuthorizationMiddleware::class])
    );

    expect($result)->toBe('done')
        ->and($executed)->toBeTrue();
});

test('AuthorizationMiddleware throws exception when authorize() returns false', function () {
    $user = new TestUser(['id' => 1]);
    $this->actingAs($user);

    $executed = false;

    $command = new class($executed) extends Command
    {
        public function __construct(private bool &$executed) {}

        public function authorize(GateContract $gate): bool
        {
            return false;
        }

        public function execute(): mixed
        {
            $this->executed = true;

            return 'done';
        }
    };

    expect(fn () => app(CommandBus::class)->execute(
        $command->withMiddleware([AuthorizationMiddleware::class])
    ))->toThrow(AuthorizationException::class)
        ->and($executed)->toBeFalse();
});

test('AuthorizationMiddleware uses Gate::allows with ability() method', function () {
    $user = new TestUser(['id' => 1]);
    $this->actingAs($user);

    // Define a test gate
    Gate::define('test-ability', fn (TestUser $user) => $user->id === 1);

    $executed = false;

    $command = new class($executed) extends Command
    {
        public function __construct(private bool &$executed) {}

        public function ability(): string
        {
            return 'test-ability';
        }

        public function execute(): mixed
        {
            $this->executed = true;

            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute(
        $command->withMiddleware([AuthorizationMiddleware::class])
    );

    expect($result)->toBe('done')
        ->and($executed)->toBeTrue();
});

test('AuthorizationMiddleware throws exception when Gate denies ability', function () {
    $user = new TestUser(['id' => 2]);
    $this->actingAs($user);

    // Define a test gate that only allows user id 1
    Gate::define('test-ability', fn (TestUser $user) => $user->id === 1);

    $executed = false;

    $command = new class($executed) extends Command
    {
        public function __construct(private bool &$executed) {}

        public function ability(): string
        {
            return 'test-ability';
        }

        public function execute(): mixed
        {
            $this->executed = true;

            return 'done';
        }
    };

    expect(fn () => app(CommandBus::class)->execute(
        $command->withMiddleware([AuthorizationMiddleware::class])
    ))->toThrow(AuthorizationException::class)
        ->and($executed)->toBeFalse();
});

test('AuthorizationMiddleware passes model to Gate with model() method', function () {
    $user = new TestUser(['id' => 1]);
    $this->actingAs($user);

    $testModel = new class
    {
        public int $userId = 1;
    };

    // Define a gate that checks model ownership
    Gate::define('update', function (TestUser $user, $model) {
        return $user->id === $model->userId;
    });

    $executed = false;

    $command = new class($executed, $testModel) extends Command
    {
        public function __construct(
            private bool &$executed,
            private object $model,
        ) {}

        public function ability(): string
        {
            return 'update';
        }

        public function model(): mixed
        {
            return $this->model;
        }

        public function execute(): mixed
        {
            $this->executed = true;

            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute(
        $command->withMiddleware([AuthorizationMiddleware::class])
    );

    expect($result)->toBe('done')
        ->and($executed)->toBeTrue();
});

test('AuthorizationMiddleware passes additional arguments with abilityArguments()', function () {
    $user = new TestUser(['id' => 1]);
    $this->actingAs($user);

    $testModel = new class
    {
        public int $userId = 1;
    };

    // Define a gate that checks model and category
    Gate::define('transfer', function (TestUser $user, $model, int $categoryId) {
        return $user->id === $model->userId && $categoryId === 5;
    });

    $executed = false;

    $command = new class($executed, $testModel) extends Command
    {
        public function __construct(
            private bool &$executed,
            private object $model,
        ) {}

        public function ability(): string
        {
            return 'transfer';
        }

        public function model(): mixed
        {
            return $this->model;
        }

        public function abilityArguments(): array
        {
            return [5]; // category ID
        }

        public function execute(): mixed
        {
            $this->executed = true;

            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute(
        $command->withMiddleware([AuthorizationMiddleware::class])
    );

    expect($result)->toBe('done')
        ->and($executed)->toBeTrue();
});

test('AuthorizationMiddleware skips authorization when no methods defined', function () {
    $user = new TestUser(['id' => 1]);
    $this->actingAs($user);

    $executed = false;

    $query = new class($executed) extends Query
    {
        public function __construct(private bool &$executed) {}

        // No authorize() or ability() method

        public function execute(): mixed
        {
            $this->executed = true;

            return 'public-data';
        }
    };

    $result = app(QueryBus::class)->execute(
        $query->withMiddleware([AuthorizationMiddleware::class])
    );

    expect($result)->toBe('public-data')
        ->and($executed)->toBeTrue();
});

test('AuthorizationMiddleware works with Query operations', function () {
    $user = new TestUser(['id' => 1]);
    $this->actingAs($user);

    Gate::define('view-data', fn (TestUser $user) => $user->id === 1);

    $executed = false;

    $query = new class($executed) extends Query
    {
        public function __construct(private bool &$executed) {}

        public function ability(): string
        {
            return 'view-data';
        }

        public function execute(): mixed
        {
            $this->executed = true;

            return 'secret-data';
        }
    };

    $result = app(QueryBus::class)->execute(
        $query->withMiddleware([AuthorizationMiddleware::class])
    );

    expect($result)->toBe('secret-data')
        ->and($executed)->toBeTrue();
});

test('AuthorizationMiddleware can be used as global middleware', function () {
    $user = new TestUser(['id' => 1]);
    $this->actingAs($user);

    Gate::define('global-check', fn (TestUser $user) => true);

    config(['pragmatic.cqrs.command_middleware' => [AuthorizationMiddleware::class]]);

    $command = new class extends Command
    {
        public function ability(): string
        {
            return 'global-check';
        }

        public function execute(): mixed
        {
            return 'done';
        }
    };

    $result = app(CommandBus::class)->execute($command);

    expect($result)->toBe('done');
});
