<?php

declare(strict_types=1);

namespace Pragmatic\StateMachine\Exceptions;

use RuntimeException;
use UnitEnum;

/**
 * Exception thrown when attempting an invalid state transition.
 */
class InvalidTransitionException extends RuntimeException
{
    public static function fromStates(?UnitEnum $from, UnitEnum $to): self
    {
        $fromName = $from ? $from->name : 'null';
        $toName = $to->name;

        return new self("Cannot transition from state '{$fromName}' to '{$toName}'");
    }
}
