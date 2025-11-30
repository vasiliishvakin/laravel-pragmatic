<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;

/**
 * Abstract base class for CQRS buses.
 *
 * Provides common functionality for QueryBus and CommandBus:
 * - Middleware collection and filtering
 * - Pipeline execution
 * - Boot and execute phases
 */
abstract class AbstractBus
{
    /**
     * Create a new Bus instance.
     *
     * @param  Container  $container  Laravel Service Container for dependency injection
     */
    public function __construct(
        protected readonly Container $container,
    ) {}

    /**
     * Execute an operation through middleware pipeline with automatic dependency injection.
     *
     * @param  Query|Command  $operation  Operation instance to execute
     * @param  string  $configKey  Config key for global middleware
     * @return mixed Operation result (may be transformed by middleware)
     */
    protected function executeOperation(Query|Command $operation, string $configKey): mixed
    {
        $middleware = $this->collectMiddleware($operation, $configKey);

        // If no middleware, execute directly for performance
        if (empty($middleware)) {
            return $this->executeWithBootstrap($operation);
        }

        // Execute through middleware pipeline
        return (new Pipeline($this->container))
            ->send($operation)
            ->through($middleware)
            ->then(fn (Query|Command $operation) => $this->executeWithBootstrap($operation));
    }

    /**
     * Collect all middleware for the operation from three sources:
     * 1. Global middleware from config
     * 2. Per-class middleware from operation->middleware()
     * 3. Runtime middleware from operation->withMiddleware()
     *
     * Then filter out excluded middleware.
     *
     * @return array<int, class-string|object>
     */
    private function collectMiddleware(Query|Command $operation, string $configKey): array
    {
        $middleware = array_merge(
            config($configKey, []),
            $operation->middleware(),
            $operation->getRuntimeMiddleware()
        );

        return $this->filterExcludedMiddleware($middleware, $operation->getExcludedMiddleware());
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

    /**
     * Execute the operation with boot() and execute() phases.
     */
    private function executeWithBootstrap(Query|Command $operation): mixed
    {
        // Call boot() for optional dependency injection if not already booted
        if (! $operation->isBooted()) {
            $this->container->call([$operation, 'boot']);
            $operation->markAsBooted();
        }

        return $this->container->call([$operation, 'execute']);
    }
}
