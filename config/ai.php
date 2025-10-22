<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI services including API keys, endpoints, and settings
    |
    */

    // Prefer AI_API_KEY, fall back to OPENAI_API_KEY if present
    'api_key' => env('AI_API_KEY', env('OPENAI_API_KEY', '')),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'timeout' => env('AI_TIMEOUT', 30),
    'max_tokens' => env('AI_MAX_TOKENS', 2000),
    'temperature' => env('AI_TEMPERATURE', 0.5),

    // Disable external AI calls in testing to avoid real network requests
    'disable_external_calls' => env('AI_DISABLE_EXTERNAL_CALLS', env('APP_ENV') === 'testing'),

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    */

    'models' => [
        'text' => env('AI_TEXT_MODEL', 'gpt-3.5-turbo'),
        'image' => env('AI_IMAGE_MODEL', 'gpt-4-vision-preview'),
        'embedding' => env('AI_EMBEDDING_MODEL', 'text-embedding-ada-002'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('AI_CACHE_PREFIX', 'ai_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'enabled' => env('AI_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('AI_RATE_LIMIT_MAX', 100),
        'per_minutes' => env('AI_RATE_LIMIT_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    */

    'fallback' => [
        'enabled' => env('AI_FALLBACK_ENABLED', true),
        'default_responses' => [
            'product_classification' => 'غير محدد',
            'sentiment' => 'محايد',
            'recommendations' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agents Scheduling Configuration
    |--------------------------------------------------------------------------
    */

    'monitor' => [
        'enabled' => env('AI_MONITOR_ENABLED', true),
    ],

    'strict_agent' => [
        'enabled' => env('AI_STRICT_AGENT_ENABLED', true),
    ],
];