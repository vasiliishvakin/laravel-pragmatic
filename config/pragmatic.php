<?php

declare(strict_types=1);

use Pragmatic\Json\Drivers\CollectionJsonDriver;
use Pragmatic\Json\Drivers\JsJsonDriver;
use Pragmatic\Json\Drivers\JsonDriver;

return [
    'hash' => [
        'driver' => 'fast',
        'algo' => env('HASH_FAST_ALGO', 'xxh3'),
    ],
    'json' => [
        'default' => env('JSON_DRIVER', 'default'),

        /*
        |--------------------------------------------------------------------------
        | Available JSON Drivers
        |--------------------------------------------------------------------------
        */
        'drivers' => [
            'default' => [
                'driver' => JsonDriver::class,
                'flags' => [
                    'encode' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ],
            ],

            'pretty' => [
                'driver' => JsonDriver::class,
                'params' => [
                    'encodeFlags' => JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ],
            ],

            'collection' => [
                'driver' => CollectionJsonDriver::class,
            ],

            'js' => [
                'driver' => JsJsonDriver::class,
            ],
        ],

    ],
    'cache' => [
        'delimiter' => env('CACHE_KEY_DELIMITER', ':'),
    ],
];
