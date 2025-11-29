<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping\Transform;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Pragmatic\Data\Attributes\MapTransform;
use Pragmatic\Data\Contracts\Mapping\MapperTransformContract;
use Pragmatic\Data\Mapping\MapperTransformer;
use Pragmatic\Reflection\ReflectionAttributeData;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

final class CollectionTransform implements MapperTransformContract
{
    public function __construct(
        private readonly Container $container,
        private readonly MapperTransformer $mapperTransformer,
        private readonly MapperTransformContract|string|null $itemTransformer = null,
        private readonly array $itemTransformerParams = [],
    ) {}

    public function resolve(ReflectionParameterData $parameter, Value $value): Value
    {
        if (! $value->exists()) {
            return Value::none();
        }

        $raw = $value->get();
        $collection = $raw instanceof Collection ? $raw : collect($raw);

        if ($this->itemTransformer) {
            $itemParameter = $this->itemParameter();
            throw_unless(
                $itemParameter instanceof ReflectionParameterData,
                InvalidArgumentException::class,
                'Unable to build item parameter for collection transform.'
            );

            $collection = $collection->map(
                fn (mixed $item) => $this->transformItem($item, $itemParameter)
            );
        }

        return Value::some($collection);
    }

    private function itemParameter(): ?ReflectionParameterData
    {
        return once(function () {
            $attributesData = [
                [
                    'name' => MapTransform::class,
                    'arguments' => [
                        'mapperClass' => $this->itemTransformer,
                        'params' => $this->itemTransformerParams,
                    ],
                ],
            ];

            $attributes = collect($attributesData)
                ->map(fn (array $attrData) => ReflectionAttributeData::fromArray($attrData));

            $parameterTypeData = [
                'type' => 'item',
                'nullable' => true,
                'union' => false,
                'intersection' => false,
                'isClass' => null,
                'isBuiltin' => null,
                'isEnum' => null,
            ];

            $parameterData = [
                'name' => 'item',
                'type' => $parameterTypeData,
                'hasDefault' => false,
                'default' => null,
                'attributes' => $attributes,
            ];

            $itemParameter = ReflectionParameterData::fromArray($parameterData);

            return $itemParameter;
        });
    }

    private function transformItem(mixed $item, ReflectionParameterData $itemParameter): mixed
    {
        $value = Value::some($item);

        $value = $this->mapperTransformer->resolve($value, $itemParameter);

        return $value->get();
    }
}
