<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hostinger Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration specific to Hostinger hosting.
    | These settings are optimized for Hostinger's environment.
    |
    */

    'server' => [
        'ip' => '45.87.81.218',
        'name' => 'nl-srv-web480.main-hosting.eu',
        'location' => 'Netherlands',
        'resources' => [
            'cpu' => 2,
            'ram' => '2GB',
            'storage' => '20GB SSD',
        ],
    ],

    'ssh' => [
        'host' => '45.87.81.218',
        'port' => 65002,
        'username' => 'u990109832',
        'path' => '/home/u990109832/public_html',
    ],

    'ftp' => [
        'host' => '45.87.81.218',
        'username' => 'u990109832',
        'path' => 'public_html',
    ],

    'database' => [
        'host' => env('HOSTINGER_DB_HOST', 'localhost'),
        'port' => env('HOSTINGER_DB_PORT', 3306),
        'database' => env('HOSTINGER_DB_DATABASE'),
        'username' => env('HOSTINGER_DB_USERNAME'),
        'password' => env('HOSTINGER_DB_PASSWORD'),
    ],

    'mail' => [
        'host' => env('HOSTINGER_MAIL_HOST', 'mail.hostinger.com'),
        'port' => env('HOSTINGER_MAIL_PORT', 587),
        'encryption' => env('HOSTINGER_MAIL_ENCRYPTION', 'tls'),
        'username' => env('HOSTINGER_MAIL_USERNAME'),
        'password' => env('HOSTINGER_MAIL_PASSWORD'),
    ],

    'ssl' => [
        'type' => 'Lifetime SSL (Google)',
        'status' => 'active',
        'created' => '2025-07-28',
        'expires' => 'never',
    ],

    'dns' => [
        'nameservers' => [
            'ns1.dns-parking.com',
            'ns2.dns-parking.com',
        ],
        'records' => [
            'a' => [
                '@' => 'coprra.com.cdn.hstgr.net',
                'ftp' => '45.87.81.218',
            ],
            'cname' => [
                'www' => 'www.coprra.com.cdn.hstgr.net',
                'autodiscover' => 'autodiscover.mail.hostinger.com',
                'autoconfig' => 'autoconfig.mail.hostinger.com',
            ],
            'mx' => [
                'mx1.hostinger.com' => 5,
                'mx2.hostinger.com' => 10,
            ],
            'txt' => [
                'spf' => 'v=spf1 include:_spf.mail.hostinger.com ~all',
                'dmarc' => 'v=DMARC1; p=none',
            ],
        ],
    ],

    'php' => [
        'version' => '8.2.28',
        'memory_limit' => '2048M',
        'max_execution_time' => 360,
        'upload_max_filesize' => '2048M',
        'post_max_size' => '2048M',
        'opcache' => [
            'enabled' => true,
            'memory_consumption' => '256M',
            'max_accelerated_files' => 16229,
            'jit' => 'tracing',
        ],
    ],

    'cdn' => [
        'url' => 'https://coprra.com.cdn.hstgr.net',
        'enabled' => true,
        'type' => 'Hostinger CDN',
        'function' => 'تسريع تحميل الملفات الثابتة',
    ],

    'backup' => [
        'enabled' => true,
        'path' => '/home/u990109832/backups',
        'retention_days' => 30,
    ],
];
