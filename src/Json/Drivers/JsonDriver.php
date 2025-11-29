<?php

declare(strict_types=1);

namespace Pragmatic\Json\Drivers;

use JsonException;
use Pragmatic\Json\JsonDriverContract;

class JsonDriver implements JsonDriverContract
{
    protected const int DEFAULT_ENCODE_FLAGS = JSON_THROW_ON_ERROR;

    protected const int DEFAULT_DECODE_FLAGS = JSON_THROW_ON_ERROR;

    protected const int DEFAULT_VALIDATE_FLAGS = 0;

    public function __construct(
        protected int $depth = 512,
        protected int $encodeFlags = 0,
        protected int $decodeFlags = 0,
        protected int $validateFlags = 0,
    ) {
        $this->encodeFlags = $encodeFlags | self::DEFAULT_ENCODE_FLAGS;
        $this->decodeFlags = $decodeFlags | self::DEFAULT_DECODE_FLAGS;
        $this->validateFlags = $validateFlags | self::DEFAULT_VALIDATE_FLAGS;
    }

    public function encode(mixed $value): string
    {
        return json_encode($value, $this->encodeFlags, $this->depth);
    }

    public function tryEncode(mixed $value): ?string
    {
        try {
            return $this->encode($value);
        } catch (JsonException) {
            return null;
        }
    }

    public function decode(string $json): mixed
    {
        return json_decode($json, true, $this->depth, $this->decodeFlags);
    }

    public function tryDecode(string $json, mixed $default = null): mixed
    {
        try {
            return $this->decode($json);
        } catch (JsonException) {
            return $default;
        }
    }

    public function validate(string $json): bool
    {
        return json_validate($json, $this->depth, $this->validateFlags);
    }
}
