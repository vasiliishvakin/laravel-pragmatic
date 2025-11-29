<?php

declare(strict_types=1);

namespace Pragmatic\Data\Attributes;

use Illuminate\Contracts\Container\Container;
use Pragmatic\Data\Contracts\Mapping\MapperContract;

abstract class AbstractMapAttribute
{
    private ?MapperContract $mapper = null;

    /**
     * @param  class-string<MapperContract>  $mapperClass
     * @param  array<string,mixed>  $params
     */
    public function __construct(
        protected readonly string $mapperClass,
        protected readonly array $params = [],
    ) {}

    public function getMapperClass(): string
    {
        return $this->mapperClass;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getMapper(Container $container): MapperContract
    {
        return $this->mapper ??= $this->makeMapper($container);
    }

    protected function makeMapper(Container $container): MapperContract
    {
        /** @var MapperContract $mapper */
        $mapper = $container->make($this->mapperClass, $this->params);

        return $mapper;
    }
}
