<?php

declare(strict_types=1);

namespace Pragmatic\Json\Drivers;

class CollectionJsonDriver extends JsonDriver
{
    public function encode(mixed $value): string
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->toArray();
        }

        return parent::encode($value);
    }

    public function decode(string $json): mixed
    {
        $data = parent::decode($json);

        return collect($data);
    }
}
