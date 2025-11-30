<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Query;
use Throwable;

/**
 * Event dispatched when a query execution fails.
 */
class QueryFailed
{
    use Dispatchable;

    public function __construct(
        public readonly Query $query,
        public readonly Throwable $exception,
        public readonly float $executionTime,
    ) {}
}
