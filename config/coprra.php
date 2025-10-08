<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | COPRRA Application Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options specific to the COPRRA
    | price comparison application.
    |
    */

    'name' => env('COPRRA_NAME', 'COPRRA'),
    'version' => '1.0.0',
    'description' => 'Advanced Price Comparison Platform',

    /*
    |--------------------------------------------------------------------------
    | Default Currency and Language
    |--------------------------------------------------------------------------
    */

    'default_currency' => env('COPRRA_DEFAULT_CURRENCY', 'USD'),
    'default_language' => env('COPRRA_DEFAULT_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Price Comparison Settings
    |--------------------------------------------------------------------------
    */

    'price_comparison' => [
        'cache_duration' => env('PRICE_CACHE_DURATION', 3600), // 1 hour
        'max_stores_per_product' => env('MAX_STORES_PER_PRODUCT', 10),
        'price_update_interval' => env('PRICE_UPDATE_INTERVAL', 6), // hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Search and Filtering
    |--------------------------------------------------------------------------
    */

    'search' => [
        'max_results' => env('SEARCH_MAX_RESULTS', 50),
        'min_query_length' => env('SEARCH_MIN_QUERY_LENGTH', 2),
        'enable_autocomplete' => env('SEARCH_ENABLE_AUTOCOMPLETE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Preferences
    |--------------------------------------------------------------------------
    */

    'user_preferences' => [
        'default_condition' => env('COPRRA_DEFAULT_CONDITION', 'new'),
        'max_price_range' => env('COPRRA_MAX_PRICE_RANGE', 10000),
        'min_price_range' => env('COPRRA_MIN_PRICE_RANGE', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rates
    |--------------------------------------------------------------------------
    */

    'exchange_rates' => [
        'USD' => env('EXCHANGE_RATE_USD', 1.0),
        'EUR' => env('EXCHANGE_RATE_EUR', 0.85),
        'GBP' => env('EXCHANGE_RATE_GBP', 0.73),
        'JPY' => env('EXCHANGE_RATE_JPY', 110.0),
        'SAR' => env('EXCHANGE_RATE_SAR', 3.75),
        'AED' => env('EXCHANGE_RATE_AED', 3.67),
        'EGP' => env('EXCHANGE_RATE_EGP', 30.9),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination and Limits
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_items_per_page' => env('DEFAULT_ITEMS_PER_PAGE', 20),
        'max_wishlist_items' => env('MAX_WISHLIST_ITEMS', 100),
        'max_price_alerts' => env('MAX_PRICE_ALERTS', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'api' => [
        'rate_limit' => env('API_RATE_LIMIT', 100), // requests per minute
        'version' => env('API_VERSION', 'v1'),
        'enable_docs' => env('API_ENABLE_DOCS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image and Media Settings
    |--------------------------------------------------------------------------
    */

    'media' => [
        'max_image_size' => env('MAX_IMAGE_SIZE', 2048), // KB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],
        'default_product_image' => '/images/default-product.png',
        'default_store_logo' => '/images/default-store.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics and Tracking
    |--------------------------------------------------------------------------
    */

    'analytics' => [
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),
        'track_user_behavior' => env('TRACK_USER_BEHAVIOR', true),
        'track_price_clicks' => env('TRACK_PRICE_CLICKS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */

    'security' => [
        'enable_2fa' => env('ENABLE_2FA', false),
        'password_min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'session_timeout' => env('SESSION_TIMEOUT', 120), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'enable_query_caching' => env('ENABLE_QUERY_CACHING', true),
        'enable_view_caching' => env('ENABLE_VIEW_CACHING', true),
        'enable_compression' => env('ENABLE_COMPRESSION', true),
    ],
];
