<?php

declare(strict_types=1);

namespace Pragmatic\Data\Contracts\Mapping;

use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\Value;

interface MapperTransformContract extends MapperContract
{
    public function resolve(ReflectionParameterData $parameter, Value $value): Value;
}
