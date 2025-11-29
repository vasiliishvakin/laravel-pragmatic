<?php

declare(strict_types=1);

namespace Pragmatic\Support\Reflection;

use Illuminate\Support\Collection;
use Pragmatic\Data\Contracts\ArraySerializable;

final class ReflectionParameterData implements ArraySerializable
{
    /**
     * @param  Collection<int, ReflectionAttributeData>  $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly ReflectionParameterTypeData $type,
        public readonly bool $hasDefault,
        public readonly mixed $default = null,
        public readonly Collection $attributes,
    ) {}

    public static function fromArray(array $data): static
    {
        $rawAttributes = $data['attributes'] ?? null;

        $attributes = match (true) {
            $rawAttributes instanceof Collection => $rawAttributes,
            is_array($rawAttributes) => collect($rawAttributes)->map(
                fn (mixed $attrData) => $attrData instanceof ReflectionAttributeData
                ? $attrData
                : ReflectionAttributeData::fromArray($attrData)
            ),
            default => collect([]),
        };

        $type = (isset($data['type']))
            ? ($data['type'] instanceof ReflectionParameterTypeData
                ? $data['type']
                : ReflectionParameterTypeData::fromArray($data['type']))
            : null;

        return new self(
            name: $data['name'],
            type: $type,
            hasDefault: $data['hasDefault'] ?? false,
            default: $data['default'] ?? null,
            attributes: $attributes,
        );
    }

    public static function fake(?string $type = null): self
    {
        return new self(
            name: 'item',
            type: ReflectionParameterTypeData::fake($type),
            hasDefault: false,
            default: null,
            attributes: collect(),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->toArray(),
            'hasDefault' => $this->hasDefault,
            'default' => $this->default,
            'attributes' => $this->attributes?->toArray(),
        ];
    }
}
