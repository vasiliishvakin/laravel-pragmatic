<?php

declare(strict_types=1);

namespace Pragmatic\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Pragmatic\Cache\CacheUtils;
use Pragmatic\Cache\MakeCacheKey;
use Pragmatic\Support\Reflection\ReflectionAttributeData;
use Pragmatic\Support\Reflection\ReflectionParameterData;
use Pragmatic\Support\Reflection\ReflectionParameterTypeData;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

final class ReflectionReader
{
    use MakeCacheKey;

    private string $cachePrefix = 'reflection';

    public function __construct(
        private readonly CacheUtils $cacheUtils
    ) {}

    public function cacheUtils(): CacheUtils
    {
        return $this->cacheUtils;
    }

    /**
     * Get ReflectionClass for given class or object.
     */
    public function class(object|string $class): ReflectionClass
    {
        $name = $this->toClass($class);

        return $this->rememberRuntime(['class', $name], fn () => new ReflectionClass($class));
    }

    /**
     * Get constructor reflection.
     */
    public function constructor(object|string $class): ?ReflectionMethod
    {
        $name = $this->toClass($class);

        return $this->rememberRuntime(['constructor', $name], fn () => $this->class($class)->getConstructor());
    }

    /**
     * Get method parameters as collection of arrays.
     *
     * @return Collection<int, array{
     *     name: string,
     *     hasDefault: bool,
     *     default?: mixed
     * }>
     */
    public function methodParams(object|string $class, string $method, bool $withAttributes = false): Collection
    {
        $name = $this->toClass($class);
        $key = $this->makeKey(['method', $name, $method, $withAttributes ? 'withAttributes' : 'noAttributes']);

        return $this->remember($key, function () use ($class, $method, $withAttributes) {
            $ref = $this->class($class);
            $meth = $method === '__construct'
                ? $ref->getConstructor()
                : $ref->getMethod($method);

            $params = $meth?->getParameters() ?? [];

            return collect($params)->map(fn (ReflectionParameter $p) => $this->extractParameter($p, $withAttributes));
        });
    }

    /**
     * Get constructor parameters as collection of arrays.
     *
     * @return Collection<int, array{
     *     name: string,
     *     hasDefault: bool,
     *     default?: mixed
     * }>
     */
    public function constructorParams(object|string $class, bool $withAttributes = false): Collection
    {
        $name = $this->toClass($class);

        $key = $this->makeKey(['constructor', $name, $withAttributes ? 'withAttributes' : 'noAttributes']);

        return $this->remember($key, function () use ($class, $withAttributes) {
            $ctor = $this->constructor($class);
            $params = $ctor?->getParameters() ?? [];

            return collect($params)
                ->map(fn (ReflectionParameter $p) => $this->extractParameter($p, $withAttributes));
        });
    }

    /**
     * Normalize object|string to class name string.
     */
    public function toClass(object|string $class): string
    {
        return is_object($class) ? $class::class : $class;
    }

    /**
     * Cache data with request-scoped memoization + persistent cache.
     */
    private function remember(string|array $key, callable $callback): mixed
    {
        $cacheKey = is_array($key) ? $this->makeKey($key) : $key;

        return Cache::memo()->remember($cacheKey, null, $callback);
    }

    /**
     * Cache data in request-scoped array driver only.
     */
    private function rememberRuntime(string|array $key, callable $callback): mixed
    {
        $cacheKey = is_array($key) ? $this->makeKey($key) : $key;

        return Cache::store('array')->remember($cacheKey, null, $callback);
    }

    /**
     * Extract parameter info.
     *
     * @return array{
     *     name: string,
     *     hasDefault: bool,
     *     default?: mixed
     * }
     */
    private function extractParameter(ReflectionParameter $param, bool $withAttributes = false): ReflectionParameterData
    {
        $paramData = [
            'name' => $param->getName(),
            'hasDefault' => $param->isDefaultValueAvailable(),
            'type' => $this->extractParameterType($param->getType()),
        ];

        if ($paramData['hasDefault']) {
            $paramData['default'] = $param->getDefaultValue();
        }

        if ($withAttributes) {
            $paramData['attributes'] = $this->extractAttributes($param);
        }

        return ReflectionParameterData::fromArray($paramData);
    }

    private function extractParameterType(ReflectionType $type): ?ReflectionParameterTypeData
    {

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $types = [];
            $isClassFlags = [];
            $isBuiltinFlags = [];
            $isEnumFlags = [];

            foreach ($type->getTypes() as $t) {
                if (! $t instanceof ReflectionNamedType) {
                    continue;
                }

                $name = $t->getName();
                if ($name === 'null') {
                    continue;
                }

                $types[] = $name;
                $isBuiltinFlags[] = $t->isBuiltin();
                $isClassFlags[] = ! $t->isBuiltin();
                $isEnumFlags[] = enum_exists($name);
            }

            $mixedClasses = count(array_unique($isClassFlags)) > 1;
            $mixedEnums = count(array_unique($isEnumFlags)) > 1;

            return new ReflectionParameterTypeData(
                type: $types,
                nullable: $type->allowsNull(),
                union: $type instanceof ReflectionUnionType,
                intersection: $type instanceof ReflectionIntersectionType,
                isClass: $mixedClasses ? null : ($isClassFlags[0] ?? null),
                isBuiltin: $mixedClasses ? null : ($isBuiltinFlags[0] ?? null),
                isEnum: $mixedEnums ? null : ($isEnumFlags[0] ?? null),
            );
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            $isBuiltin = $type->isBuiltin();
            $isEnum = ! $isBuiltin && enum_exists($name);

            return new ReflectionParameterTypeData(
                type: $name,
                nullable: $type->allowsNull(),
                union: false,
                intersection: false,
                isClass: ! $isBuiltin,
                isBuiltin: $isBuiltin,
                isEnum: $isEnum,
            );
        }

        return null;
    }

    private function extractAttributes(object $reflection): Collection
    {
        if (! method_exists($reflection, 'getAttributes')) {
            return collect();
        }

        return collect($reflection->getAttributes())
            ->map(fn (ReflectionAttribute $attr) => ReflectionAttributeData::fromArray([
                'name' => $attr->getName(),
                'arguments' => $attr->getArguments(),
            ])
            );
    }
}
