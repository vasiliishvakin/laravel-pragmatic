<?php

declare(strict_types=1);

namespace Pragmatic\Json;

use BadMethodCallException;
use InvalidArgumentException;
use Pragmatic\Hashing\FastHasher;

final class JsonManager implements JsonDriverContract, JsonManagerInstance
{
    public function __construct(
        protected readonly JsonFactoryContainer $factoryContainer,
        protected readonly FastHasher $hasher,
        protected readonly ?JsonDriverContract $driver = null,
    ) {}

    public function __call(string $method, array $args): mixed
    {
        $driver = $this->instance()->rawDriver();

        if (! method_exists($driver, $method)) {
            throw new BadMethodCallException("Method {$method} does not exist on driver ".get_class($driver));
        }

        return $driver->$method(...$args);
    }

    public function driver(?string $name = null): static
    {
        $name ??= config('toolbox.json.default');

        return $this->factoryContainer->get($name);
    }

    public function rawDriver(): JsonDriverContract
    {
        if ($this->driver === null) {
            throw new InvalidArgumentException('No JSON driver has been set.');
        }

        return $this->driver;
    }

    public function hasDriver(): bool
    {
        return $this->driver !== null;
    }

    public function instance(): static
    {
        return $this->hasDriver() ? $this : $this->driver();
    }

    public function encode(mixed $value): string
    {
        return $this->instance()->rawDriver()->encode($value);
    }

    public function decode(string $value): mixed
    {
        return $this->instance()->rawDriver()->decode($value);
    }

    public function tryEncode(mixed $value): ?string
    {
        return $this->instance()->rawDriver()->tryEncode($value);
    }

    public function tryDecode(string $json, mixed $default = null): mixed
    {
        return $this->instance()->rawDriver()->tryDecode($json, $default);
    }

    public function validate(string $json): bool
    {
        return $this->instance()->rawDriver()->validate($json);
    }

    public function hash(mixed $value): string
    {
        return $this->hasher->make($this->encode($value));
    }
}
