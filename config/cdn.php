<?php

return [
    'default' => env('CDN_DEFAULT', 'cloudflare'),

    'drivers' => [
        'cloudflare' => [
            'url' => env('CLOUDFLARE_URL'),
            'zone_id' => env('CLOUDFLARE_ZONE_ID'),
            'api_token' => env('CLOUDFLARE_API_TOKEN'),
        ],
        'aws' => [
            'url' => env('AWS_CDN_URL'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'access_key' => env('AWS_ACCESS_KEY_ID'),
            'secret_key' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ],

    'cache_control' => [
        'images' => 'max-age=31536000', // 1 year
        'css' => 'max-age=31536000',
        'js' => 'max-age=31536000',
        'fonts' => 'max-age=31536000',
    ],
];
