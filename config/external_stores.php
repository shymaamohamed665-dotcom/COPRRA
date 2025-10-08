<?php

return [
    'amazon' => [
        'api_url' => env('AMAZON_API_URL', 'https://api.amazon.com/products'),
        'api_key' => env('AMAZON_API_KEY'),
        'rate_limit' => 100, // requests per minute
    ],
    'ebay' => [
        'api_url' => env('EBAY_API_URL', 'https://api.ebay.com/buy/browse/v1'),
        'api_key' => env('EBAY_API_KEY'),
        'rate_limit' => 5000, // requests per day
    ],
    'aliexpress' => [
        'api_url' => env('ALIEXPRESS_API_URL', 'https://api.aliexpress.com/products'),
        'api_key' => env('ALIEXPRESS_API_KEY'),
        'rate_limit' => 200, // requests per minute
    ],
];
