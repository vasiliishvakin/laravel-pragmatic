<?php

declare(strict_types=1);

namespace Pragmatic\Support\Reflection;

use Pragmatic\Data\Contracts\ArraySerializable;

class ReflectionParameterTypeData implements ArraySerializable
{
    public function __construct(
        public readonly string|array $type,
        public readonly bool $nullable,
        public readonly bool $union,
        public readonly bool $intersection,
        public readonly ?bool $isClass,
        public readonly ?bool $isBuiltin,
        public readonly ?bool $isEnum
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            type: $data['type'],
            nullable: $data['nullable'],
            union: $data['union'],
            intersection: $data['intersection'],
            isClass: $data['isClass'] ?? null,
            isBuiltin: $data['isBuiltin'] ?? null,
            isEnum: $data['isEnum'] ?? null,
        );
    }

    public static function fake(?string $type = null): self
    {
        $isClass = $type ? class_exists($type) : null;
        $isEnum = $type && enum_exists($type);

        return new self(
            type: $type ?? 'mixed',
            nullable: true,
            union: false,
            intersection: false,
            isClass: $isClass,
            isBuiltin: $type ? ! $isClass : true,
            isEnum: $isEnum,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'nullable' => $this->nullable,
            'union' => $this->union,
            'intersection' => $this->intersection,
            'isClass' => $this->isClass,
            'isBuiltin' => $this->isBuiltin,
            'isEnum' => $this->isEnum,
        ];
    }
}
