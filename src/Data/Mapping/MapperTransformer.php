<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Pragmatic\Data\Attributes\MapTransform;
use Pragmatic\Facades\Json;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

final class MapperTransformer
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Apply all MapTransform attributes to a given Value.
     */
    public function resolve(Value $value, ReflectionParameterData $param): Value
    {
        $attributes = $param->attributes;

        foreach ($attributes as $attributeData) {
            if ($attributeData->name !== MapTransform::class) {
                continue;
            }

            $args = $attributeData->arguments ?? [];
            if (! Arr::isAssoc($args)) {
                $args = array_get_many($args, ['mapperClass', 'params']);
            }

            throw_if(count($args) < 1, InvalidArgumentException::class, 'Mapper class is required.');

            $cacheKey = 'transformer:'.MapTransform::class.':'.Json::hash($args);

            /** @var MapTransform $mapTransform */
            $mapTransform = Cache::store('array')->remember(
                $cacheKey,
                null,
                fn () => $this->container->make(MapTransform::class, $args)
            );

            // Transform current Value
            $newValue = $mapTransform->resolve($this->container, $param, $value);

            throw_unless(
                $newValue instanceof Value,
                InvalidArgumentException::class,
                'MapperTransform must return instance of Value.'
            );

            // Replace the current value for next transform (chainable)
            $value = $newValue;
        }

        return $value;
    }
}
