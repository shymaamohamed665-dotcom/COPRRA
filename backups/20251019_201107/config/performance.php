<?php

declare(strict_types=1);

return [
    'cache' => [
        'enabled' => env('CACHE_ENABLED', true),
        'default_ttl' => env('CACHE_DEFAULT_TTL', 3600),
        'user_analytics_ttl' => env('CACHE_USER_ANALYTICS_TTL', 1800),
        'product_recommendations_ttl' => env('CACHE_PRODUCT_RECOMMENDATIONS_TTL', 3600),
        'site_analytics_ttl' => env('CACHE_SITE_ANALYTICS_TTL', 3600),
    ],

    'database' => [
        'connection_pooling' => env('DB_CONNECTION_POOLING', true),
        'max_connections' => env('DB_MAX_CONNECTIONS', 100),
        'query_cache' => env('DB_QUERY_CACHE', true),
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
    ],

    'images' => [
        'optimization' => env('IMAGE_OPTIMIZATION', true),
        'webp_conversion' => env('IMAGE_WEBP_CONVERSION', true),
        'lazy_loading' => env('IMAGE_LAZY_LOADING', true),
        'responsive_images' => env('IMAGE_RESPONSIVE', true),
        'quality' => env('IMAGE_QUALITY', 85),
    ],

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'provider' => env('CDN_PROVIDER', 'cloudflare'),
        'cache_control' => [
            'images' => 'max-age=31536000',
            'css' => 'max-age=31536000',
            'js' => 'max-age=31536000',
            'fonts' => 'max-age=31536000',
        ],
    ],

    'compression' => [
        'gzip' => env('COMPRESSION_GZIP', true),
        'brotli' => env('COMPRESSION_BROTLI', true),
        'minify_html' => env('COMPRESSION_MINIFY_HTML', true),
        'minify_css' => env('COMPRESSION_MINIFY_CSS', true),
        'minify_js' => env('COMPRESSION_MINIFY_JS', true),
    ],

    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING', true),
        'slow_query_logging' => env('SLOW_QUERY_LOGGING', true),
        'memory_usage_tracking' => env('MEMORY_USAGE_TRACKING', true),
        'response_time_tracking' => env('RESPONSE_TIME_TRACKING', true),
    ],
];
