<?php

declare(strict_types=1);

namespace Pragmatic\Alerts;

enum AlertType: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Success = 'success';
    case Info = 'info';

}
