<?php

declare(strict_types=1);

namespace Pragmatic\Data\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Pragmatic\Data\Contracts\Mapping\MapperTransformContract;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class MapTransform extends AbstractMapAttribute
{
    public function resolve(Container $container, ReflectionParameterData $property, Value $value): Value
    {
        /**
         * @var MapperTransformContract
         */
        $mapper = $this->getMapper($container);

        return $mapper->resolve($property, $value);
    }
}
