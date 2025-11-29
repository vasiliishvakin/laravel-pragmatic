<?php

declare(strict_types=1);

use Modules\Toolbox\Debug\Drivers\CoreDriver;
use Modules\Toolbox\Debug\Drivers\LaradumpsDriver;
use Modules\Toolbox\Debug\Drivers\LogDriver;

return [

    /*
    |--------------------------------------------------------------------------
    | Debug Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether debugging is enabled globally. When disabled,
    | all debug operations will be silently suppressed unless the 'force-debug'
    | Pennant feature is active for the current context.
    |
    | In production (APP_ENV=production), this is automatically set to false
    | unless explicitly enabled via DEBUG_ENABLED=true environment variable.
    |
    */

    'enabled' => env('DEBUG_ENABLED', env('APP_ENV') !== 'production'),

    /*
    |--------------------------------------------------------------------------
    | Default Debug Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default debug driver that will be used when
    | no specific driver is requested. Available drivers: core, laradumps, log
    |
    */

    'default' => env('DEBUG_DRIVER', 'core'),

    /*
    |--------------------------------------------------------------------------
    | Debug Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the debug drivers for your application.
    |
    | Available drivers:
    | - core: Uses Symfony VarDumper for rich variable inspection
    | - laradumps: Uses LaraDumps for external debugging interface
    | - log: Writes debug output to Laravel logs
    |
    */

    'drivers' => [

        'core' => [
            'driver' => CoreDriver::class,
            'options' => [
                // Maximum depth for nested structures
                'max_depth' => env('DEBUG_CORE_MAX_DEPTH', 10),

                // Maximum string length before truncation
                'max_string_length' => env('DEBUG_CORE_MAX_STRING', 1000),

                // Show resource information
                'show_resources' => env('DEBUG_CORE_SHOW_RESOURCES', true),
            ],
        ],

        'laradumps' => [
            'driver' => LaradumpsDriver::class,
            'options' => [
                // LaraDumps screen name for organized output
                'screen' => env('DEBUG_LARADUMPS_SCREEN', 'Debug'),

                // Auto-clear screen on new dump
                'auto_clear' => env('DEBUG_LARADUMPS_AUTO_CLEAR', false),
            ],
        ],

        'log' => [
            'driver' => LogDriver::class,
            'options' => [
                // Default log channel
                'channel' => env('DEBUG_LOG_CHANNEL', config('logging.default')),

                // Default log level
                'level' => env('DEBUG_LOG_LEVEL', 'debug'),

                // Include backtrace in log output
                'backtrace' => env('DEBUG_LOG_BACKTRACE', true),

                // Backtrace depth limit
                'backtrace_depth' => env('DEBUG_LOG_BACKTRACE_DEPTH', 5),
            ],
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
