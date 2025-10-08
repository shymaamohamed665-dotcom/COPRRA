<?php

declare(strict_types=1);

namespace Tests\TestUtilities;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;

/**
 * Advanced Test Helper for comprehensive testing.
 *
 * This class provides advanced testing utilities for:
 * - Complex mocking scenarios
 * - Database transaction management
 * - Performance testing
 * - Security testing
 * - Integration testing
 */
class AdvancedTestHelper
{
    use RefreshDatabase;

    private array $mockRegistry = [];

    private array $performanceMetrics = [];

    private array $securityChecks = [];

    /**
     * Create advanced service mock with comprehensive method coverage.
     */
    public function createAdvancedServiceMock(string $serviceClass, array $methods = []): Mockery\MockInterface
    {
        $mock = Mockery::mock($serviceClass);

        // Default method implementations for common service patterns
        $defaultMethods = [
            'create' => fn ($data) => ['id' => 1, ...$data],
            'update' => fn ($id, $data) => ['id' => $id, ...$data],
            'delete' => fn ($id) => true,
            'find' => fn ($id) => ['id' => $id],
            'all' => fn () => [],
            'paginate' => fn ($perPage = 15) => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage),
        ];

        foreach (array_merge($defaultMethods, $methods) as $method => $implementation) {
            $mock->shouldReceive($method)->andReturnUsing($implementation);
        }

        $this->mockRegistry[$serviceClass] = $mock;

        return $mock;
    }

    /**
     * Create comprehensive facade mock with chained methods.
     */
    public function createFacadeMock(string $facadeClass, array $chainMethods = []): Mockery\MockInterface
    {
        $mock = Mockery::mock($facadeClass);

        foreach ($chainMethods as $method => $returnValue) {
            if (is_array($returnValue)) {
                $mock->shouldReceive($method)->andReturnUsing(function (...$args) use ($returnValue) {
                    return $this->handleChainedMethod($returnValue, $args);
                });
            } else {
                $mock->shouldReceive($method)->andReturn($returnValue);
            }
        }

        return $mock;
    }

    /**
     * Handle chained method calls for facades.
     */
    private function handleChainedMethod(array $chainConfig, array $args)
    {
        $current = $chainConfig;
        foreach ($args as $arg) {
            if (isset($current[$arg])) {
                $current = $current[$arg];
            } else {
                return $current['default'] ?? null;
            }
        }

        return $current;
    }

    /**
     * Create database transaction test with rollback.
     */
    public function withDatabaseTransaction(callable $callback): mixed
    {
        DB::beginTransaction();

        try {
            $result = $callback();
            DB::rollback();

            return $result;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Create performance test with metrics collection.
     */
    public function withPerformanceTest(callable $callback, int $maxExecutionTime = 1000): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $result = $callback();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $metrics = [
            'execution_time' => ($endTime - $startTime) * 1000, // milliseconds
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage(),
            'within_time_limit' => ($endTime - $startTime) * 1000 <= $maxExecutionTime,
        ];

        $this->performanceMetrics[] = $metrics;

        if (! $metrics['within_time_limit']) {
            throw new \Exception("Performance test failed: Execution time exceeded {$maxExecutionTime}ms");
        }

        return $result;
    }

    /**
     * Create security test with vulnerability checks.
     */
    public function withSecurityTest(callable $callback, array $securityRules = []): array
    {
        $securityChecks = [
            'sql_injection' => false,
            'xss_vulnerability' => false,
            'csrf_protection' => true,
            'authentication_required' => true,
            'authorization_check' => true,
        ];

        $result = $callback();

        // Perform security checks
        foreach ($securityRules as $rule => $expected) {
            $securityChecks[$rule] = $this->performSecurityCheck($rule, $result);
        }

        $this->securityChecks[] = $securityChecks;

        return $result;
    }

    /**
     * Perform specific security check.
     */
    private function performSecurityCheck(string $rule, mixed $result): bool
    {
        switch ($rule) {
            case 'sql_injection':
                return ! $this->containsSqlInjection($result);
            case 'xss_vulnerability':
                return ! $this->containsXssVulnerability($result);
            case 'csrf_protection':
                return $this->hasCsrfProtection();
            default:
                return true;
        }
    }

    /**
     * Check for SQL injection patterns.
     */
    private function containsSqlInjection(mixed $data): bool
    {
        $sqlPatterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/delete\s+from/i',
            '/insert\s+into/i',
            '/update\s+set/i',
            '/\';\s*--/i',
            '/\';\s*\/\*/i',
        ];

        $dataString = is_string($data) ? $data : json_encode($data);

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $dataString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for XSS vulnerability patterns.
     */
    private function containsXssVulnerability(mixed $data): bool
    {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>.*?<\/iframe>/i',
        ];

        $dataString = is_string($data) ? $data : json_encode($data);

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $dataString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check CSRF protection.
     */
    private function hasCsrfProtection(): bool
    {
        return app('session')->has('_token');
    }

    /**
     * Create comprehensive test data factory.
     */
    public function createTestData(string $type, array $overrides = []): array
    {
        $factories = [
            'user' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
            'product' => [
                'name' => 'Test Product',
                'description' => 'Test Description',
                'price' => 99.99,
                'category_id' => 1,
                'brand_id' => 1,
            ],
            'order' => [
                'user_id' => 1,
                'total' => 199.98,
                'status' => 'pending',
                'items' => [
                    ['product_id' => 1, 'quantity' => 2, 'price' => 99.99],
                ],
            ],
        ];

        $data = $factories[$type] ?? [];

        return array_merge($data, $overrides);
    }

    /**
     * Create mock external API response.
     */
    public function createMockApiResponse(array $data = [], int $statusCode = 200): array
    {
        return [
            'status' => $statusCode,
            'data' => $data,
            'message' => $statusCode === 200 ? 'Success' : 'Error',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Create comprehensive error scenario.
     */
    public function createErrorScenario(string $errorType, array $context = []): \Exception
    {
        $errors = [
            'validation' => new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['field' => ['The field is required.']]
            ),
            'not_found' => new \Illuminate\Database\Eloquent\ModelNotFoundException,
            'unauthorized' => new \Illuminate\Auth\AuthenticationException,
            'forbidden' => new \Illuminate\Auth\Access\AuthorizationException('Access denied'),
            'server_error' => new \Exception('Internal server error', 500),
        ];

        return $errors[$errorType] ?? new \Exception('Unknown error');
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * Get security checks.
     */
    public function getSecurityChecks(): array
    {
        return $this->securityChecks;
    }

    /**
     * Clean up all mocks.
     */
    public function cleanup(): void
    {
        Mockery::close();
        $this->mockRegistry = [];
        $this->performanceMetrics = [];
        $this->securityChecks = [];
    }

    /**
     * Assert performance requirements.
     */
    public function assertPerformanceRequirements(array $requirements = []): void
    {
        $defaultRequirements = [
            'max_execution_time' => 1000, // ms
            'max_memory_usage' => 50 * 1024 * 1024, // 50MB
            'max_queries' => 10,
        ];

        $requirements = array_merge($defaultRequirements, $requirements);

        foreach ($this->performanceMetrics as $metrics) {
            if ($metrics['execution_time'] > $requirements['max_execution_time']) {
                throw new \Exception("Performance requirement failed: Execution time {$metrics['execution_time']}ms exceeds limit {$requirements['max_execution_time']}ms");
            }

            if ($metrics['memory_usage'] > $requirements['max_memory_usage']) {
                throw new \Exception("Performance requirement failed: Memory usage {$metrics['memory_usage']} bytes exceeds limit {$requirements['max_memory_usage']} bytes");
            }
        }
    }

    /**
     * Assert security requirements.
     */
    public function assertSecurityRequirements(array $requirements = []): void
    {
        $defaultRequirements = [
            'no_sql_injection' => true,
            'no_xss_vulnerability' => true,
            'csrf_protection' => true,
        ];

        $requirements = array_merge($defaultRequirements, $requirements);

        foreach ($this->securityChecks as $checks) {
            foreach ($requirements as $requirement => $expected) {
                if (isset($checks[$requirement]) && $checks[$requirement] !== $expected) {
                    throw new \Exception("Security requirement failed: {$requirement} check failed");
                }
            }
        }
    }
}
