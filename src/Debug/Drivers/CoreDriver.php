<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Drivers;

use Pragmatic\Debug\Contracts\CoreDebugDriver;
use Pragmatic\Debug\Contracts\DebugDriver;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

final class CoreDriver implements CoreDebugDriver
{



    /**
     * Dump variable(s) and continue execution.
     */
    public function dump(mixed ...$vars): mixed
    {
        return dump(...$vars);
    }

    /**
     * Dump variable(s) and die (stop execution).
     */
    public function dd(mixed ...$vars): never
    {
        dd(...$vars);
    }


}
