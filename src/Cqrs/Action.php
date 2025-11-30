<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

use Illuminate\Pipeline\Pipeline;

/**
 * Base class for all actions in the CQRS pattern.
 *
 * Actions orchestrate business logic by composing Query and Command operations.
 * Unlike Query/Command which use buses, Actions are executed directly via run().
 *
 * Supports middleware:
 * - Global middleware (from config)
 * - Per-class middleware (from middleware() method)
 * - Runtime middleware (from withMiddleware() method)
 *
 * Dispatches lifecycle events via EventMiddleware:
 * - ActionExecuting (before execution)
 * - ActionExecuted (after successful execution)
 * - ActionFailed (on exception)
 *
 * Usage:
 * ```php
 * class PublishPostAction extends Action
 * {
 *     public function __construct(
 *         private readonly int $postId,
 *     ) {}
 *
 *     public function execute(): void
 *     {
 *         $post = QueryBus::execute(new GetPostQuery($this->postId));
 *         CommandBus::execute(new PublishPostCommand($post));
 *         CommandBus::execute(new NotifySubscribersCommand($post));
 *     }
 * }
 *
 * // Execute directly
 * PublishPostAction::make(postId: 123)->run();
 *
 * // With runtime middleware
 * PublishPostAction::make(postId: 123)
 *     ->withMiddleware(LoggingMiddleware::class)
 *     ->run();
 * ```
 */
abstract class Action extends BaseOperation
{
    /**
     * Execute the action through middleware pipeline.
     *
     * This method automatically:
     * - Collects middleware from config, class, and runtime sources
     * - Builds and executes middleware pipeline
     * - Calls execute() method with dependency injection
     * - Returns the result (may be void)
     *
     * @return mixed Action result (may be void)
     */
    public function run(): mixed
    {
        $middleware = $this->collectMiddleware();

        // If no middleware, execute directly for performance
        if (empty($middleware)) {
            return app()->call([$this, 'execute']);
        }

        // Execute through middleware pipeline
        return (new Pipeline(app()))
            ->send($this)
            ->through($middleware)
            ->then(fn (Action $action) => app()->call([$action, 'execute']));
    }

    /**
     * Collect all middleware for the action from three sources:
     * 1. Global middleware from config
     * 2. Per-class middleware from middleware() method
     * 3. Runtime middleware from withMiddleware()
     *
     * Then filter out excluded middleware.
     *
     * @return array<int, class-string|object>
     */
    private function collectMiddleware(): array
    {
        $middleware = array_merge(
            config('pragmatic.cqrs.action_middleware', []),
            $this->middleware(),
            $this->getRuntimeMiddleware()
        );

        return $this->filterExcludedMiddleware($middleware, $this->getExcludedMiddleware());
    }

    /**
     * Filter out excluded middleware from the collection.
     *
     * @param  array<int, class-string|object>  $middleware
     * @param  array<int, class-string>  $excluded
     * @return array<int, class-string|object>
     */
    private function filterExcludedMiddleware(array $middleware, array $excluded): array
    {
        if (empty($excluded)) {
            return $middleware;
        }

        return array_filter($middleware, function ($item) use ($excluded) {
            $className = is_string($item) ? $item : get_class($item);

            return ! in_array($className, $excluded, true);
        });
    }
}
