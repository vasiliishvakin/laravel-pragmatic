<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Operation;

/**
 * Authorization middleware for CQRS operations using Laravel policies.
 *
 * Checks authorization before operation execution using standard Laravel Gate/Policy system.
 * If authorization fails, throws AuthorizationException to short-circuit execution.
 *
 * Usage patterns:
 *
 * 1. Using authorize() method (recommended):
 * ```php
 * class UpdatePostCommand extends Command
 * {
 *     public function __construct(
 *         private readonly Post $post,
 *         private readonly string $title,
 *     ) {}
 *
 *     public function middleware(): array
 *     {
 *         return [AuthorizationMiddleware::class];
 *     }
 *
 *     // Define authorization logic
 *     public function authorize(Gate $gate): bool
 *     {
 *         return $gate->allows('update', $this->post);
 *     }
 *
 *     public function execute(PostRepository $repository): Post
 *     {
 *         return $repository->update($this->post, $this->title);
 *     }
 * }
 * ```
 *
 * 2. Using ability() method for simple ability name:
 * ```php
 * class DeletePostCommand extends Command
 * {
 *     public function __construct(private readonly Post $post) {}
 *
 *     // Returns ability name for Gate check
 *     public function ability(): string
 *     {
 *         return 'delete';
 *     }
 *
 *     // Returns model for policy resolution
 *     public function model(): mixed
 *     {
 *         return $this->post;
 *     }
 *
 *     public function execute(): void
 *     {
 *         $this->post->delete();
 *     }
 * }
 * ```
 *
 * 3. Using ability() with additional arguments:
 * ```php
 * class TransferPostCommand extends Command
 * {
 *     public function __construct(
 *         private readonly Post $post,
 *         private readonly int $categoryId,
 *     ) {}
 *
 *     public function ability(): string
 *     {
 *         return 'transfer';
 *     }
 *
 *     public function model(): mixed
 *     {
 *         return $this->post;
 *     }
 *
 *     // Optional: additional arguments for policy method
 *     public function abilityArguments(): array
 *     {
 *         return [$this->categoryId];
 *     }
 * }
 * ```
 *
 * 4. Skip authorization for specific operations:
 * ```php
 * class PublicQuery extends Query
 * {
 *     // No authorize() or ability() method = skip authorization
 *     public function execute(): array
 *     {
 *         return ['public' => 'data'];
 *     }
 * }
 * ```
 */
final class AuthorizationMiddleware implements Middleware
{
    public function __construct(
        private readonly Gate $gate,
    ) {}

    /**
     * Handle the operation execution with authorization check.
     *
     * @throws AuthorizationException
     */
    public function handle(Operation $operation, Closure $next): mixed
    {
        // Check if operation has custom authorize() method
        if (method_exists($operation, 'authorize')) {
            $authorized = $this->gate->getPolicyFor($operation)
                ? $operation->authorize($this->gate)
                : app()->call([$operation, 'authorize']);

            if (! $authorized) {
                throw new AuthorizationException('This action is unauthorized.');
            }

            return $next($operation);
        }

        // Check if operation has ability() method for simple Gate check
        if (method_exists($operation, 'ability')) {
            $ability = $operation->ability();
            $model = method_exists($operation, 'model') ? $operation->model() : null;
            $arguments = method_exists($operation, 'abilityArguments')
                ? $operation->abilityArguments()
                : [];

            // Build arguments array for Gate::authorize
            $gateArguments = $model !== null
                ? array_merge([$model], $arguments)
                : $arguments;

            $this->gate->authorize($ability, $gateArguments);

            return $next($operation);
        }

        // No authorization methods defined - skip authorization
        return $next($operation);
    }
}
