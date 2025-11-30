<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Query;

/**
 * Event dispatched after a query is successfully executed.
 */
class QueryExecuted
{
    use Dispatchable;

    public function __construct(
        public readonly Query $query,
        public readonly mixed $result,
        public readonly float $executionTime,
    ) {}
}
