<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

/**
 * Base class for all commands in the CQRS pattern.
 *
 * Commands are write operations that modify state in any destination
 * (database, API, cache, files, queues, etc.).
 *
 * Supports two-phase initialization:
 * 1. Constructor - receives data parameters
 * 2. boot() - optional dependency injection phase (called automatically by CommandBus)
 *
 * Usage:
 * ```php
 * class CreateOrderCommand extends Command
 * {
 *     public function __construct(
 *         private readonly int $userId,
 *         private readonly array $items,
 *     ) {}
 *
 *     // Optional: inject dependencies before execute()
 *     public function boot(EventDispatcher $events): void
 *     {
 *         $this->events = $events;
 *     }
 *
 *     public function execute(OrderRepository $repository): Order
 *     {
 *         return $repository->create([
 *             'user_id' => $this->userId,
 *             'items' => $this->items,
 *         ]);
 *     }
 * }
 *
 * // Execute via CommandBus
 * $order = CommandBus::execute(
 *     CreateOrderCommand::make(userId: 1, items: [...])
 * );
 * ```
 */
abstract class Command extends Operation
{
    //
}
