<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Methods: Restrict to those actually needed (REST + preflight)
    'allowed_methods' => array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')))),

    // Origins: Environment-based. Defaults:
    // - local: common dev origins (Vite/SPA)
    // - production: APP_URL and FRONTEND_URL only
    'allowed_origins' => (static function (): array {
        $env = (string) env('APP_ENV', 'production');
        $fromEnv = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))));

        if ($fromEnv !== []) {
            return $fromEnv;
        }

        if ($env === 'local' || $env === 'development') {
            return [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
                'http://localhost:3000',
                'http://127.0.0.1:3000',
                (string) env('APP_URL'),
            ];
        }

        $defaults = [];
        if (env('APP_URL')) {
            $defaults[] = (string) env('APP_URL');
        }
        if (env('FRONTEND_URL')) {
            $defaults[] = (string) env('FRONTEND_URL');
        }

        return $defaults;
    })(),

    'allowed_origins_patterns' => [],

    // Headers: Only those required by the app
    'allowed_headers' => array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_HEADERS', 'Accept,Authorization,Content-Type,X-Requested-With,X-CSRF-TOKEN')))),

    // Exposed headers: usually none; configurable via env
    'exposed_headers' => array_filter(array_map('trim', explode(',', (string) env('CORS_EXPOSED_HEADERS', '')))),

    // Cache preflight responses
    'max_age' => (int) env('CORS_MAX_AGE', 600),

    // Credentials: only enable when required (e.g., Sanctum SPA)
    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),
];
