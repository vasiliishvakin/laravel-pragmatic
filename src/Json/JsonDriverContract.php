<?php

declare(strict_types=1);

namespace Pragmatic\Json;

interface JsonDriverContract
{
    public function encode(mixed $value): string;

    public function tryEncode(mixed $value): ?string;

    public function decode(string $json): mixed;

    public function tryDecode(string $json, mixed $default = null): mixed;

    public function validate(string $json): bool;
}
