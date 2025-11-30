<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pragmatic\Cqrs\Query;

/**
 * Event dispatched before a query is executed.
 */
class QueryExecuting
{
    use Dispatchable;

    public function __construct(
        public readonly Query $query,
    ) {}
}
