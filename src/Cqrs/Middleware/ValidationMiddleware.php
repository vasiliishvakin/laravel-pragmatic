<?php

declare(strict_types=1);

namespace Pragmatic\Cqrs\Middleware;

use Closure;
use Illuminate\Validation\ValidationException;
use Pragmatic\Cqrs\Contracts\Middleware;
use Pragmatic\Cqrs\Operation;

/**
 * Validation middleware for CQRS operations.
 *
 * Validates operations before execution by checking for a validate() method.
 * If validation fails, throws ValidationException to short-circuit execution.
 *
 * To use this middleware, implement a validate() method on your Query/Command:
 *
 * ```php
 * class CreateUserCommand extends Command
 * {
 *     public function __construct(
 *         private readonly string $email,
 *         private readonly string $name,
 *     ) {}
 *
 *     public function middleware(): array
 *     {
 *         return [ValidationMiddleware::class];
 *     }
 *
 *     // Optional: implement validation
 *     public function validate(): void
 *     {
 *         if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
 *             throw ValidationException::withMessages([
 *                 'email' => ['Invalid email format'],
 *             ]);
 *         }
 *     }
 *
 *     public function execute(): void
 *     {
 *         // Execute only if validation passes
 *     }
 * }
 * ```
 */
final class ValidationMiddleware implements Middleware
{
    /**
     * Handle the operation execution with validation.
     *
     * @throws ValidationException
     */
    public function handle(Operation $operation, Closure $next): mixed
    {
        // If the operation has a validate() method, call it
        if (method_exists($operation, 'validate')) {
            $operation->validate();
        }

        // If validation passed (no exception thrown), continue
        return $next($operation);
    }
}
