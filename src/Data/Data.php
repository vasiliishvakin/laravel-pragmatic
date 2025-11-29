<?php

declare(strict_types=1);

namespace Pragmatic\Data;

use Pragmatic\Facades\DataFactory;
use Pragmatic\Facades\DataSerializer;

abstract class Data
{
    public static function from(array $data): static
    {
        return DataFactory::make(static::class, $data);
    }

    public function toArray(): array
    {
        return DataSerializer::toArray($this);
    }
}
