<?php

declare(strict_types=1);

namespace Pragmatic\Json\Drivers;

use Illuminate\Support\Js;

class JsJsonDriver extends JsonDriver
{
    public function js(mixed $value): Js
    {
        return Js::from($value, $this->encodeFlags, $this->depth);
    }

    public function encode(mixed $value): string
    {
        return $this->js($value)->toHtml();
    }
}
