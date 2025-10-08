<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => env('OPENAI_TIMEOUT', 30),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 2000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.5),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'key' => env('STRIPE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Adapters Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external store API integrations.
    | These are used by COPRRA store adapters to fetch product data.
    |
    */

    'amazon' => [
        'api_key' => env('AMAZON_API_KEY'),
        'api_secret' => env('AMAZON_API_SECRET'),
        'associate_tag' => env('AMAZON_ASSOCIATE_TAG'),
        'region' => env('AMAZON_REGION', 'us-east-1'),
    ],

    'ebay' => [
        'app_id' => env('EBAY_APP_ID'),
        'cert_id' => env('EBAY_CERT_ID'),
        'dev_id' => env('EBAY_DEV_ID'),
        'site_id' => env('EBAY_SITE_ID', '0'),
    ],

    'noon' => [
        'api_key' => env('NOON_API_KEY'),
        'country' => env('NOON_COUNTRY', 'ae'),
    ],
];
