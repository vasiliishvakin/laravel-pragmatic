<?php

declare(strict_types=1);

namespace Pragmatic\Data;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Pragmatic\Data\Attributes\MapField;
use Pragmatic\Facades\Reflection;
use Pragmatic\Support\Value;

abstract class DataOld
{
    use ToArray;

    protected static array $fieldMap = [];

    public function __construct(...$args) {}

    public static function from(mixed $data): static
    {
        if (is_array($data)) {
            return static::fromArray($data);
        }

        throw new InvalidArgumentException('Data must be an array.');
    }

    protected static function fromArray(array $data): static
    {
        // $data = static::applyFieldMap($data);

        $args = static::extractArgs(
            Reflection::constructorParams(static::class),
            $data
        );

        return new static(...$args);
    }

    protected static function extractArgs(Collection $params, array $data): array
    {
        return $params
            ->map(fn ($param) => $this->extractArgValue($param, $data))
            ->all();
    }

    protected function extractArgValue(array $param, array $data): Value
    {
        throw_unless(isset($param['name']), InvalidArgumentException::class, 'Parameter name is required.');
        $name = $param['name'];

        return Value::maybe(
            array_key_exists($name, $data),
            $data[$name]
        )
            ->or(fn () => $this->extractWithMappers($param, $data))
            ->map(fn ($v) => $this->transformValue($param, $v));
    }

    protected function extractWithMappers(array $param, array $data): Value
    {
        $attributes = $param['attributes'] ?? [];

        if (empty($attributes)) {
            return Value::none();
        }

        foreach ($attributes as $attributeData) {
            if (($attributeData['name'] ?? null) !== MapField::class) {
                continue;
            }

            $args = $attributeData['arguments'] ?? [];

            throw_if(empty($args['mapperClass']), InvalidArgumentException::class, 'Mapper class is required.');

            $mapper = app()->make($args['mapperClass'], $args['params'] ?? []);

            throw_unless(
                $mapper instanceof MapperInterface,
                InvalidArgumentException::class,
                sprintf('Mapper "%s" must implement MapperInterface.', $args['mapperClass'])
            );

            /** @var Value $result */
            $result = $mapper($param, $data);

            throw_unless(
                $result instanceof Value,
                InvalidArgumentException::class,
                sprintf('Mapper "%s" must return instance of Value.', $args['mapperClass'])
            );

            if ($result->exists()) {
                return $result;
            }
        }

        return Value::none();
    }

    protected function transformValue(array $param, mixed $value): mixed
    {
        return $value;
    }
}
