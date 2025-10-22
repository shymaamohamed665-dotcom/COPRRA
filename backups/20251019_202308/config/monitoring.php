<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for application monitoring and
    | performance tracking.
    |
    */

    'enabled' => env('MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for performance monitoring and metrics collection.
    |
    */
    'performance' => [
        'enabled' => env('PERFORMANCE_MONITORING', true),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'memory_threshold' => env('MEMORY_THRESHOLD', 128), // MB
        'execution_time_threshold' => env('EXECUTION_TIME_THRESHOLD', 5), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for error tracking and reporting.
    |
    */
    'errors' => [
        'enabled' => env('ERROR_MONITORING', true),
        'report_level' => env('ERROR_REPORT_LEVEL', 'error'),
        'max_errors_per_minute' => env('MAX_ERRORS_PER_MINUTE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for database performance monitoring.
    |
    */
    'database' => [
        'enabled' => env('DB_MONITORING', true),
        'slow_query_log' => env('DB_SLOW_QUERY_LOG', true),
        'connection_monitoring' => env('DB_CONNECTION_MONITORING', true),
        'query_count_threshold' => env('DB_QUERY_COUNT_THRESHOLD', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for cache performance monitoring.
    |
    */
    'cache' => [
        'enabled' => env('CACHE_MONITORING', true),
        'hit_rate_threshold' => env('CACHE_HIT_RATE_THRESHOLD', 0.8), // 80%
        'miss_rate_threshold' => env('CACHE_MISS_RATE_THRESHOLD', 0.2), // 20%
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for queue performance monitoring.
    |
    */
    'queue' => [
        'enabled' => env('QUEUE_MONITORING', true),
        'failed_job_threshold' => env('FAILED_JOB_THRESHOLD', 10),
        'queue_size_threshold' => env('QUEUE_SIZE_THRESHOLD', 1000),
        'processing_time_threshold' => env('QUEUE_PROCESSING_TIME_THRESHOLD', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | API Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for API performance and usage monitoring.
    |
    */
    'api' => [
        'enabled' => env('API_MONITORING', true),
        'rate_limit_threshold' => env('API_RATE_LIMIT_THRESHOLD', 1000), // requests per minute
        'response_time_threshold' => env('API_RESPONSE_TIME_THRESHOLD', 2000), // milliseconds
        'error_rate_threshold' => env('API_ERROR_RATE_THRESHOLD', 0.05), // 5%
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for storage usage monitoring.
    |
    */
    'storage' => [
        'enabled' => env('STORAGE_MONITORING', true),
        'disk_usage_threshold' => env('DISK_USAGE_THRESHOLD', 80), // percentage
        'log_size_threshold' => env('LOG_SIZE_THRESHOLD', 100), // MB
        'temp_file_threshold' => env('TEMP_FILE_THRESHOLD', 50), // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for alerting and notifications.
    |
    */
    'alerts' => [
        'enabled' => env('ALERTS_ENABLED', true),
        'channels' => [
            'email' => [
                'enabled' => env('ALERT_EMAIL_ENABLED', true),
                'recipients' => explode(',', (string) env('ALERT_EMAIL_RECIPIENTS', '')),
            ],
            'slack' => [
                'enabled' => env('ALERT_SLACK_ENABLED', false),
                'webhook_url' => env('ALERT_SLACK_WEBHOOK_URL'),
                'channel' => env('ALERT_SLACK_CHANNEL', '#alerts'),
            ],
            'webhook' => [
                'enabled' => env('ALERT_WEBHOOK_ENABLED', false),
                'url' => env('ALERT_WEBHOOK_URL'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Configuration for metrics collection and storage.
    |
    */
    'metrics' => [
        'enabled' => env('METRICS_ENABLED', true),
        'storage' => env('METRICS_STORAGE', 'database'), // database, redis, file
        'retention_days' => env('METRICS_RETENTION_DAYS', 30),
        'collection_interval' => env('METRICS_COLLECTION_INTERVAL', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    |
    | Configuration for health check endpoints.
    |
    */
    'health_checks' => [
        'enabled' => env('HEALTH_CHECKS_ENABLED', true),
        'endpoint' => env('HEALTH_CHECKS_ENDPOINT', '/api/health'),
        'checks' => [
            'database' => env('HEALTH_CHECK_DATABASE', true),
            'cache' => env('HEALTH_CHECK_CACHE', true),
            'storage' => env('HEALTH_CHECK_STORAGE', true),
            'queue' => env('HEALTH_CHECK_QUEUE', true),
        ],
    ],
];
