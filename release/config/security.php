<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | These are the security headers that should be set on all responses
    |
    */
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        // Set a static baseline CSP to satisfy tests
        'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self';",
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
        // HSTS is environment-driven to avoid preloading in non-production.
        'Strict-Transport-Security' => [
            'enabled' => env('HSTS_ENABLED', env('APP_ENV') === 'production'),
            'value' => 'max-age='.env('HSTS_MAX_AGE', 31536000)
                .(env('HSTS_INCLUDE_SUBDOMAINS', true) ? '; includeSubDomains' : '')
                .(env('HSTS_PRELOAD', false) ? '; preload' : ''),
            'conditions' => [
                'https_only' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Requirements
    |--------------------------------------------------------------------------
    |
    | These are the requirements for passwords in the application
    |
    */
    'passwords' => [
        'min_length' => 12,
        'require_numbers' => true,
        'require_symbols' => true,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'prevent_common_passwords' => true,
        'max_attempts' => 5,
        'lockout_time' => 15, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | These are the security settings for file uploads
    |
    */
    'uploads' => [
        'max_size' => 10240, // 10MB
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'pdf',
            'doc', 'docx', 'xls', 'xlsx', 'txt',
            'csv', 'zip',
        ],
        'scan_for_viruses' => env('UPLOAD_VIRUS_SCAN', true),
        'sanitize_filenames' => true,
        'validate_mime_types' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    |
    | These are the security settings for the API
    |
    */
    'api' => [
        'throttle' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
        'require_https' => env('API_REQUIRE_HTTPS', true),
        'token_expiration' => 60, // minutes
        'refresh_token_expiration' => 20160, // 14 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | These are the rate limiting settings for different operations
    |
    */
    'rate_limiting' => [
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        'register' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
        'password_reset' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
        'api_requests' => [
            'max_attempts' => 1000,
            'decay_minutes' => 60,
        ],
        'ai_requests' => [
            'max_attempts' => 100,
            'decay_minutes' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Scanning
    |--------------------------------------------------------------------------
    |
    | These are the settings for security scanning
    |
    */
    'scanning' => [
        'enabled' => env('SECURITY_SCANNING_ENABLED', true),
        'scan_uploads' => true,
        'scan_dependencies' => true,
        'notify_on_vulnerabilities' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | These are the authentication security settings
    |
    */
    'authentication' => [
        'require_2fa' => env('REQUIRE_2FA', false),
        'session_lifetime' => env('SESSION_LIFETIME', 120), // minutes
        'remember_me_lifetime' => 20160, // 14 days
        'password_history' => 5, // number of previous passwords to check
        'password_expiry' => 90, // days
    ],
];
