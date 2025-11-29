<?php

declare(strict_types=1);

namespace Pragmatic\Data;

use Pragmatic\Data\Mapping\MapperResolver;
use Pragmatic\Data\Mapping\MapperTransformer;
use Pragmatic\Reflection\ReflectionParameterData;
use Pragmatic\Support\DataAccessor;
use Pragmatic\Support\ReflectionReader;
use Pragmatic\Support\Value;

final class DataFactoryService
{
    public function __construct(
        private readonly ReflectionReader $reflection,
        private readonly MapperResolver $resolver,
        private readonly DataAccessor $dataAccessor,
        private readonly MapperTransformer $transformer,
    ) {}

    public function make(string $class, mixed $data): object
    {
        $params = $this->reflection->constructorParams($class, true);

        $args = collect($params)
            ->mapWithKeys(function (ReflectionParameterData $param) use ($data) {
                $name = $param->name;
                $value = $this->extractArgValue($param, $data);

                return [$name => $value];
            })
            ->filter(fn (Value $value) => $value->exists())
            ->map(fn (Value $value) => $value->get())
            ->all();

        return new $class(...$args);
    }

    protected function extractArgValue(ReflectionParameterData $param, mixed $data): Value
    {
        $name = $param->name;

        $value = $this->dataAccessor
            ->get($data, $name)
            ->or(fn () => $this->resolver->resolve($data, $param))
            ->then(fn (Value $v) => $this->transformer->resolve($v, $param));

        return $value;
    }
}
