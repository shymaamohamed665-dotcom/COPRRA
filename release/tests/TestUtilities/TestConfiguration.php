<?php

declare(strict_types=1);

namespace Tests\TestUtilities;

/**
 * Test Configuration for comprehensive testing setup.
 *
 * This class provides centralized configuration for all test suites including:
 * - Test environment setup
 * - Database configuration
 * - Mock configurations
 * - Performance thresholds
 * - Security requirements
 * - Coverage requirements
 */
class TestConfiguration
{
    // Test Environment Configuration
    public const TEST_ENVIRONMENT = 'testing';

    public const TEST_DATABASE = 'testing';

    public const TEST_CACHE_DRIVER = 'array';

    public const TEST_QUEUE_DRIVER = 'sync';

    public const TEST_MAIL_DRIVER = 'array';

    public const TEST_SESSION_DRIVER = 'array';

    // Performance Test Configuration
    public const PERFORMANCE_THRESHOLDS = [
        'unit_test_max_time' => 100, // milliseconds
        'integration_test_max_time' => 500, // milliseconds
        'api_test_max_time' => 1000, // milliseconds
        'database_query_max_time' => 50, // milliseconds
        'memory_limit_mb' => 50,
        'max_queries_per_test' => 10,
    ];

    // Security Test Configuration
    public const SECURITY_REQUIREMENTS = [
        'min_password_strength' => 80,
        'max_login_attempts' => 5,
        'session_timeout_minutes' => 30,
        'csrf_protection_required' => true,
        'xss_protection_required' => true,
        'sql_injection_protection_required' => true,
        'encryption_required' => true,
    ];

    // Coverage Requirements
    public const COVERAGE_REQUIREMENTS = [
        'overall_coverage_min' => 95.0,
        'line_coverage_min' => 94.0,
        'function_coverage_min' => 96.0,
        'class_coverage_min' => 98.0,
        'method_coverage_min' => 97.0,
        'critical_path_coverage_min' => 100.0,
    ];

    // Test Data Configuration
    public const TEST_DATA = [
        'users_count' => 100,
        'products_count' => 500,
        'categories_count' => 20,
        'orders_count' => 200,
        'reviews_count' => 1000,
    ];

    // Mock Configuration
    public const MOCK_CONFIGURATIONS = [
        'external_apis' => [
            'openai' => [
                'base_url' => 'https://api.openai.com/v1',
                'timeout' => 30,
                'mock_responses' => true,
            ],
            'payment_gateway' => [
                'base_url' => 'https://api.stripe.com/v1',
                'timeout' => 15,
                'mock_responses' => true,
            ],
        ],
        'email_services' => [
            'smtp' => [
                'host' => 'localhost',
                'port' => 1025,
                'mock' => true,
            ],
        ],
        'storage_services' => [
            'local' => [
                'disk' => 'test',
                'mock' => true,
            ],
            's3' => [
                'bucket' => 'test-bucket',
                'mock' => true,
            ],
        ],
    ];

    // Test Suite Configuration
    public const TEST_SUITES = [
        'unit' => [
            'enabled' => true,
            'parallel' => true,
            'timeout' => 300, // seconds
            'memory_limit' => '256M',
        ],
        'integration' => [
            'enabled' => true,
            'parallel' => false,
            'timeout' => 600, // seconds
            'memory_limit' => '512M',
        ],
        'performance' => [
            'enabled' => true,
            'parallel' => false,
            'timeout' => 1800, // seconds
            'memory_limit' => '1G',
        ],
        'security' => [
            'enabled' => true,
            'parallel' => false,
            'timeout' => 900, // seconds
            'memory_limit' => '512M',
        ],
        'api' => [
            'enabled' => true,
            'parallel' => true,
            'timeout' => 300, // seconds
            'memory_limit' => '256M',
        ],
    ];

    // Database Test Configuration
    public const DATABASE_CONFIG = [
        'migrations' => [
            'run_before_tests' => true,
            'run_after_tests' => false,
            'seed_before_tests' => true,
        ],
        'transactions' => [
            'use_transactions' => true,
            'rollback_after_tests' => true,
        ],
        'factories' => [
            'create_real_data' => false,
            'use_fake_data' => true,
        ],
    ];

    // API Test Configuration
    public const API_CONFIG = [
        'base_url' => 'http://localhost:8000/api',
        'timeout' => 30,
        'retry_attempts' => 3,
        'rate_limiting' => [
            'enabled' => true,
            'max_requests_per_minute' => 60,
        ],
        'authentication' => [
            'type' => 'bearer',
            'token_expiry' => 3600, // seconds
        ],
    ];

    // Performance Test Configuration
    public const PERFORMANCE_CONFIG = [
        'load_testing' => [
            'concurrent_users' => [1, 5, 10, 20, 50],
            'duration_seconds' => 60,
            'ramp_up_seconds' => 10,
        ],
        'stress_testing' => [
            'max_concurrent_users' => 100,
            'duration_seconds' => 300,
        ],
        'memory_testing' => [
            'max_memory_usage_mb' => 100,
            'memory_leak_threshold' => 10, // MB per minute
        ],
    ];

    // Security Test Configuration
    public const SECURITY_CONFIG = [
        'vulnerability_scanning' => [
            'sql_injection' => true,
            'xss' => true,
            'csrf' => true,
            'authentication_bypass' => true,
            'authorization_bypass' => true,
        ],
        'penetration_testing' => [
            'enabled' => true,
            'tools' => ['sqlmap', 'burp_suite', 'owasp_zap'],
        ],
        'data_protection' => [
            'encryption_at_rest' => true,
            'encryption_in_transit' => true,
            'pii_protection' => true,
        ],
    ];

    // Coverage Test Configuration
    public const COVERAGE_CONFIG = [
        'drivers' => ['pcov', 'xdebug'],
        'reports' => ['html', 'clover', 'cobertura'],
        'exclude_directories' => [
            'vendor/',
            'node_modules/',
            'storage/',
            'bootstrap/cache/',
        ],
        'exclude_files' => [
            '*.blade.php',
            '*.js',
            '*.css',
        ],
    ];

    // Test Data Factories Configuration
    public const FACTORY_CONFIG = [
        'user_factory' => [
            'default_attributes' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
            ],
            'variations' => [
                'admin' => ['role' => 'admin'],
                'customer' => ['role' => 'customer'],
            ],
        ],
        'product_factory' => [
            'default_attributes' => [
                'name' => 'Test Product',
                'price' => 99.99,
                'description' => 'Test Description',
            ],
            'variations' => [
                'expensive' => ['price' => 999.99],
                'cheap' => ['price' => 9.99],
            ],
        ],
    ];

    // Error Handling Configuration
    public const ERROR_HANDLING_CONFIG = [
        'expected_errors' => [
            'validation_errors' => 422,
            'authentication_errors' => 401,
            'authorization_errors' => 403,
            'not_found_errors' => 404,
            'server_errors' => 500,
        ],
        'error_logging' => [
            'enabled' => true,
            'level' => 'error',
            'channels' => ['single', 'slack'],
        ],
    ];

    // Notification Configuration
    public const NOTIFICATION_CONFIG = [
        'channels' => [
            'email' => [
                'enabled' => true,
                'mock' => true,
            ],
            'sms' => [
                'enabled' => false,
                'mock' => true,
            ],
            'push' => [
                'enabled' => false,
                'mock' => true,
            ],
        ],
        'templates' => [
            'welcome' => 'emails.welcome',
            'password_reset' => 'emails.password_reset',
            'order_confirmation' => 'emails.order_confirmation',
        ],
    ];

    // Cache Configuration
    public const CACHE_CONFIG = [
        'drivers' => ['array', 'redis', 'memcached'],
        'default_driver' => 'array',
        'ttl' => [
            'default' => 3600, // 1 hour
            'short' => 300,    // 5 minutes
            'long' => 86400,   // 24 hours
        ],
        'tags' => [
            'products' => 'product_cache',
            'users' => 'user_cache',
            'categories' => 'category_cache',
        ],
    ];

    // Queue Configuration
    public const QUEUE_CONFIG = [
        'drivers' => ['sync', 'database', 'redis'],
        'default_driver' => 'sync',
        'retry_attempts' => 3,
        'retry_delay' => 60, // seconds
        'timeout' => 300, // seconds
        'jobs' => [
            'email' => 'App\\Jobs\\SendEmail',
            'notification' => 'App\\Jobs\\SendNotification',
            'backup' => 'App\\Jobs\\ProcessBackup',
        ],
    ];

    /**
     * Get configuration value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = self::getNestedValue(self::getAllConfig(), $keys);

        return $value !== null ? $value : $default;
    }

    /**
     * Get all configuration.
     */
    public static function getAllConfig(): array
    {
        return [
            'performance_thresholds' => self::PERFORMANCE_THRESHOLDS,
            'security_requirements' => self::SECURITY_REQUIREMENTS,
            'coverage_requirements' => self::COVERAGE_REQUIREMENTS,
            'test_data' => self::TEST_DATA,
            'mock_configurations' => self::MOCK_CONFIGURATIONS,
            'test_suites' => self::TEST_SUITES,
            'database_config' => self::DATABASE_CONFIG,
            'api_config' => self::API_CONFIG,
            'performance_config' => self::PERFORMANCE_CONFIG,
            'security_config' => self::SECURITY_CONFIG,
            'coverage_config' => self::COVERAGE_CONFIG,
            'factory_config' => self::FACTORY_CONFIG,
            'error_handling_config' => self::ERROR_HANDLING_CONFIG,
            'notification_config' => self::NOTIFICATION_CONFIG,
            'cache_config' => self::CACHE_CONFIG,
            'queue_config' => self::QUEUE_CONFIG,
        ];
    }

    /**
     * Get nested configuration value.
     */
    private static function getNestedValue(array $config, array $keys): mixed
    {
        $current = $config;

        foreach ($keys as $key) {
            if (! isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Validate configuration.
     */
    public static function validate(): array
    {
        $errors = [];

        // Validate performance thresholds
        if (self::PERFORMANCE_THRESHOLDS['unit_test_max_time'] <= 0) {
            $errors[] = 'Unit test max time must be greater than 0';
        }

        // Validate security requirements
        if (
            self::SECURITY_REQUIREMENTS['min_password_strength'] < 0 ||
            self::SECURITY_REQUIREMENTS['min_password_strength'] > 100
        ) {
            $errors[] = 'Password strength must be between 0 and 100';
        }

        // Validate coverage requirements
        foreach (self::COVERAGE_REQUIREMENTS as $key => $value) {
            if ($value < 0 || $value > 100) {
                $errors[] = "Coverage requirement {$key} must be between 0 and 100";
            }
        }

        return $errors;
    }

    /**
     * Get test environment variables.
     */
    public static function getTestEnvironmentVariables(): array
    {
        return [
            'APP_ENV' => self::TEST_ENVIRONMENT,
            'DB_DATABASE' => self::TEST_DATABASE,
            'CACHE_DRIVER' => self::TEST_CACHE_DRIVER,
            'QUEUE_CONNECTION' => self::TEST_QUEUE_DRIVER,
            'MAIL_MAILER' => self::TEST_MAIL_DRIVER,
            'SESSION_DRIVER' => self::TEST_SESSION_DRIVER,
        ];
    }

    /**
     * Get performance thresholds for specific test type.
     */
    public static function getPerformanceThresholds(string $testType): array
    {
        $thresholds = self::PERFORMANCE_THRESHOLDS;

        switch ($testType) {
            case 'unit':
                return [
                    'max_time' => $thresholds['unit_test_max_time'],
                    'memory_limit' => $thresholds['memory_limit_mb'],
                    'max_queries' => $thresholds['max_queries_per_test'],
                ];
            case 'integration':
                return [
                    'max_time' => $thresholds['integration_test_max_time'],
                    'memory_limit' => $thresholds['memory_limit_mb'],
                    'max_queries' => $thresholds['max_queries_per_test'],
                ];
            case 'api':
                return [
                    'max_time' => $thresholds['api_test_max_time'],
                    'memory_limit' => $thresholds['memory_limit_mb'],
                    'max_queries' => $thresholds['max_queries_per_test'],
                ];
            default:
                return $thresholds;
        }
    }

    /**
     * Get security requirements for specific test type.
     */
    public static function getSecurityRequirements(string $testType): array
    {
        $requirements = self::SECURITY_REQUIREMENTS;

        switch ($testType) {
            case 'authentication':
                return [
                    'min_password_strength' => $requirements['min_password_strength'],
                    'max_login_attempts' => $requirements['max_login_attempts'],
                    'session_timeout_minutes' => $requirements['session_timeout_minutes'],
                ];
            case 'data_protection':
                return [
                    'encryption_required' => $requirements['encryption_required'],
                    'csrf_protection_required' => $requirements['csrf_protection_required'],
                ];
            case 'input_validation':
                return [
                    'xss_protection_required' => $requirements['xss_protection_required'],
                    'sql_injection_protection_required' => $requirements['sql_injection_protection_required'],
                ];
            default:
                return $requirements;
        }
    }

    /**
     * Get coverage requirements for specific test type.
     */
    public static function getCoverageRequirements(string $testType): array
    {
        $requirements = self::COVERAGE_REQUIREMENTS;

        switch ($testType) {
            case 'critical':
                return [
                    'overall_coverage_min' => $requirements['critical_path_coverage_min'],
                    'line_coverage_min' => $requirements['critical_path_coverage_min'],
                    'function_coverage_min' => $requirements['critical_path_coverage_min'],
                ];
            case 'standard':
                return $requirements;
            default:
                return $requirements;
        }
    }
}
