<?php

declare(strict_types=1);

namespace Pragmatic\Support\Reflection;

use Pragmatic\Data\Contracts\ArraySerializable;

final class ReflectionAttributeData implements ArraySerializable
{
    public function __construct(
        public readonly string $name,
        public readonly ?array $arguments = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'],
            arguments: $data['arguments'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }
}
