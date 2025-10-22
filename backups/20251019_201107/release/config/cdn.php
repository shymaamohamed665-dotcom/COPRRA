<?php

declare(strict_types=1);

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

    // Asset delivery configuration (environment-driven)
    'asset' => [
        // Toggle custom CDN asset logic (reserved for future use)
        'enabled' => env('CDN_ENABLED', false),

        // Primary CDN base URL for assets
        'primary_url' => env('CDN_URL'),

        // Optional: multiple CDN base URLs (comma-separated) for failover/rotation
        // e.g. "https://cdn1.example.com,https://cdn2.example.com"
        'urls' => array_filter(array_map('trim', explode(',', env('CDN_URLS', '')))),

        // Whether to attempt failover to secondary URLs if primary is unreachable
        'failover' => (bool) env('CDN_FAILOVER', true),

        // Timeout for health checks or URL head requests (in milliseconds)
        'timeout_ms' => (int) env('CDN_TIMEOUT_MS', 500),
    ],
];
