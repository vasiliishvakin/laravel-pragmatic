<?php

declare(strict_types=1);

namespace Pragmatic\Data\Attributes;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Pragmatic\Data\Contracts\Mapping\MapperFieldContract;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class MapField extends AbstractMapAttribute
{
    public function resolve(Container $container, ReflectionParameterData $parameter, mixed $data): Value
    {
        /**
         * @var MapperFieldContract
         */
        $mapper = $this->getMapper($container);

        return $mapper->resolve($parameter, $data);
    }
}
