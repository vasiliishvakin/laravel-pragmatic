<?php

declare(strict_types=1);

namespace Pragmatic\Data\Mapping;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Pragmatic\Data\Attributes\MapField;
use Pragmatic\Facades\Json;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\DataAccessor;
use Pragmatic\Support\Value;

final class MapperResolver
{
    public function __construct(
        private readonly Container $container,
        private readonly DataAccessor $dataAccessor,
    ) {}

    public function resolve(mixed $data, ReflectionParameterData $param): Value
    {
        $attributes = $param->attributes;

        foreach ($attributes as $attributeData) {
            if ($attributeData->name !== MapField::class) {
                continue;
            }

            $args = $attributeData->arguments ?? [];
            if (! Arr::isAssoc($args)) {
                $args = array_get_many($args, ['mapperClass', 'params']);
            }
            throw_if(count($args) < 1, InvalidArgumentException::class, 'Mapper class is required.');

            $cacheKey = 'mapper:'.MapField::class.':'.Json::hash($args);

            /** @var MapField $mapField */
            $mapField = Cache::store('array')->remember(
                $cacheKey,
                null,
                fn () => $this->container->make(MapField::class, $args)
            );

            $value = $mapField->resolve($this->container, $param, $data);

            throw_unless(
                $value instanceof Value,
                InvalidArgumentException::class,
                'Mapper must return instance of Value.'
            );

            if ($value->exists()) {
                return $value;
            }
        }

        return Value::none();
    }
}
