<?php

namespace Pragmatic\Debug\Enums;

enum DebugMode: string
{
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Silent = 'silent';

    case Auto = 'auto';
}
