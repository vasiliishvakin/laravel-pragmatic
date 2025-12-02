<?php

declare(strict_types=1);

use Pragmatic\Debug\Drivers\LaraDumpsDriver;
use Pragmatic\Debug\Drivers\LaravelDriver;
use Pragmatic\Debug\Drivers\LogDriver;
use Pragmatic\Debug\Drivers\PhpDriver;
use Pragmatic\Debug\Drivers\SilentLogDriver;

return [

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | This option controls the debug mode for your application.
    |
    | Available modes:
    | - auto: Automatically determine based on Pennant flag, config, and APP_ENV
    | - enabled: Debugging always enabled
    | - disabled: Debugging always disabled (returns NullDriver)
    | - silent: Logging without stopping execution (uses SilentLogDriver)
    |
    */

    'mode' => env('DEBUG_MODE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Debug Enabled (for Auto mode)
    |--------------------------------------------------------------------------
    |
    | This option is used when mode is 'auto' to determine if debugging
    | should be enabled. In production (APP_ENV=production), this is
    | automatically set to false unless explicitly enabled.
    |
    | The 'force-debug' Pennant feature flag can override this setting.
    |
    */

    'enabled' => env('DEBUG_ENABLED', env('APP_ENV') !== 'production'),

    /*
    |--------------------------------------------------------------------------
    | Default Debug Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default debug driver that will be used when
    | no specific driver is requested.
    |
    */

    'default' => env('DEBUG_DRIVER', 'laravel'),

    /*
    |--------------------------------------------------------------------------
    | Debug Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the debug drivers for your application.
    |
    | Available drivers:
    | - laravel: Uses Laravel's dump() and dd() helpers
    | - php: Uses native PHP var_dump() and print_r()
    | - log: Writes debug output to Laravel logs
    | - silent-log: Logs without stopping execution (dd() doesn't die)
    | - laradumps: Uses LaraDumps for external debugging interface
    |
    */

    'drivers' => [

        'laravel' => [
            'driver' => LaravelDriver::class,
        ],

        'php' => [
            'driver' => PhpDriver::class,
        ],

        'log' => [
            'driver' => LogDriver::class,
        ],

        'silent-log' => [
            'driver' => SilentLogDriver::class,
        ],

        'laradumps' => [
            'driver' => LaraDumpsDriver::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Pennant Feature Integration
    |--------------------------------------------------------------------------
    |
    | The feature flag name that can override the 'enabled' setting.
    | When this feature is active, debugging will be enabled even in production.
    |
    */

    'pennant_feature' => 'force-debug',

];
