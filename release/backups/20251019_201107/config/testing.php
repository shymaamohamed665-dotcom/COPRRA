<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for testing environment.
    | Used for PHPUnit tests and local development.
    |
    */

    'database' => [
        'default' => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ],
    ],

    'mail' => [
        'default' => 'log',
        'mailers' => [
            'log' => [
                'transport' => 'log',
                'channel' => env('MAIL_LOG_CHANNEL'),
            ],
        ],
    ],

    'cache' => [
        'default' => 'array',
        'stores' => [
            'array' => [
                'driver' => 'array',
                'serialize' => false,
            ],
        ],
    ],

    'session' => [
        // Allow overriding via .env.testing; default to 'file' to persist across requests
        'driver' => env('SESSION_DRIVER', 'file'),
    ],

    'queue' => [
        'default' => 'sync',
    ],
];
