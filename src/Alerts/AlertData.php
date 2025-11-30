<?php

declare(strict_types=1);

namespace Pragmatic\Alerts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final class AlertData implements Arrayable, Jsonable, JsonSerializable, Stringable
{
    public const ID_PREFIX = 'alert_';

    public function __construct(
        public readonly AlertType $type,
        public readonly string $message,
        public readonly string $id,
    ) {}

    public function __toString(): string
    {
        return $this->message;
    }

    public static function make(AlertType $type, string $message, ?string $id = null): self
    {
        if (empty($message)) {
            throw new InvalidArgumentException('Message cannot be empty.');
        }
        $id = $id ?: uniqid(self::ID_PREFIX, true);

        return new self($type, $message, $id);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'message' => $this->message,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
