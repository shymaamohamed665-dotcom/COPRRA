<?php

declare(strict_types=1);

namespace Tests\TestUtilities;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Performance Test Suite for comprehensive performance testing.
 *
 * This suite provides advanced performance testing capabilities including:
 * - Load testing
 * - Memory profiling
 * - Database query optimization
 * - API response time testing
 * - Concurrent user simulation
 */
class PerformanceTestSuite
{
    use RefreshDatabase;

    private array $performanceMetrics = [];

    private array $loadTestResults = [];

    private array $memoryProfiles = [];

    /**
     * Run comprehensive performance tests for all services.
     */
    public function runComprehensivePerformanceTests(): array
    {
        $results = [
            'services' => [],
            'database' => [],
            'api_endpoints' => [],
            'memory_usage' => [],
            'concurrent_users' => [],
        ];

        // Test all services
        $results['services'] = $this->testAllServicesPerformance();

        // Test database performance
        $results['database'] = $this->testDatabasePerformance();

        // Test API endpoints
        $results['api_endpoints'] = $this->testApiEndpointsPerformance();

        // Test memory usage
        $results['memory_usage'] = $this->testMemoryUsage();

        // Test concurrent users
        $results['concurrent_users'] = $this->testConcurrentUsers();

        return $results;
    }

    /**
     * Test performance of all services.
     */
    private function testAllServicesPerformance(): array
    {
        $services = [
            'AIService' => function () {
                Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'test']]]], 200)]);
                $service = new \App\Services\AIService;

                return $service->analyzeText('Test text', 'sentiment');
            },
            'CacheService' => function () {
                Cache::shouldReceive('get')->andReturn(null);
                Cache::shouldReceive('put')->andReturn(true);
                $service = new \App\Services\CacheService;

                return $service->remember('test_key', fn () => 'test_data', 3600);
            },
            'ProductService' => function () {
                Cache::shouldReceive('get')->andReturn(null);
                Cache::shouldReceive('put')->andReturn(true);
                $service = new \App\Services\ProductService;

                return $service->getPaginatedProducts(1, 15);
            },
            'PasswordPolicyService' => function () {
                $service = new \App\Services\PasswordPolicyService;

                return $service->validatePassword('StrongPass123!');
            },
            'FinancialTransactionService' => function () {
                DB::shouldReceive('beginTransaction')->andReturn(true);
                DB::shouldReceive('commit')->andReturn(true);
                Log::shouldReceive('info')->andReturn(true);
                $service = new \App\Services\FinancialTransactionService;

                return $service->processPayment(['amount' => 100, 'currency' => 'USD', 'payment_method' => 'card', 'user_id' => 1]);
            },
        ];

        $results = [];
        foreach ($services as $serviceName => $testFunction) {
            $results[$serviceName] = $this->measureServicePerformance($serviceName, $testFunction);
        }

        return $results;
    }

    /**
     * Measure performance of a single service.
     */
    private function measureServicePerformance(string $serviceName, callable $testFunction): array
    {
        $iterations = 100;
        $executionTimes = [];
        $memoryUsages = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            try {
                $testFunction();
            } catch (\Exception $e) {
                // Continue testing even if individual calls fail
            }

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            $executionTimes[] = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsages[] = $endMemory - $startMemory;
        }

        return [
            'average_execution_time' => array_sum($executionTimes) / count($executionTimes),
            'min_execution_time' => min($executionTimes),
            'max_execution_time' => max($executionTimes),
            'average_memory_usage' => array_sum($memoryUsages) / count($memoryUsages),
            'max_memory_usage' => max($memoryUsages),
            'iterations' => $iterations,
            'success_rate' => $this->calculateSuccessRate($executionTimes),
        ];
    }

    /**
     * Test database performance.
     */
    private function testDatabasePerformance(): array
    {
        $results = [];

        // Test simple queries
        $results['simple_queries'] = $this->testDatabaseQueries([
            'SELECT * FROM users LIMIT 10',
            'SELECT COUNT(*) FROM products',
            'SELECT * FROM categories WHERE active = 1',
        ]);

        // Test complex queries
        $results['complex_queries'] = $this->testDatabaseQueries([
            'SELECT p.*, c.name as category_name, b.name as brand_name FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.price BETWEEN 10 AND 100
             ORDER BY p.created_at DESC LIMIT 50',
            'SELECT u.id, u.name, COUNT(o.id) as order_count, SUM(o.total) as total_spent
             FROM users u
             LEFT JOIN orders o ON u.id = o.user_id
             GROUP BY u.id, u.name
             HAVING COUNT(o.id) > 0
             ORDER BY total_spent DESC LIMIT 20',
        ]);

        // Test insert performance
        $results['insert_performance'] = $this->testInsertPerformance();

        // Test update performance
        $results['update_performance'] = $this->testUpdatePerformance();

        return $results;
    }

    /**
     * Test database queries performance.
     */
    private function testDatabaseQueries(array $queries): array
    {
        $results = [];

        foreach ($queries as $query) {
            $iterations = 50;
            $executionTimes = [];

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);

                try {
                    DB::select($query);
                } catch (\Exception $e) {
                    // Continue testing even if queries fail
                }

                $endTime = microtime(true);
                $executionTimes[] = ($endTime - $startTime) * 1000;
            }

            $results[] = [
                'query' => substr($query, 0, 100).'...',
                'average_time' => array_sum($executionTimes) / count($executionTimes),
                'min_time' => min($executionTimes),
                'max_time' => max($executionTimes),
                'iterations' => $iterations,
            ];
        }

        return $results;
    }

    /**
     * Test insert performance.
     */
    private function testInsertPerformance(): array
    {
        $iterations = 100;
        $executionTimes = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            try {
                DB::table('test_performance')->insert([
                    'name' => 'Test '.$i,
                    'value' => $i * 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Continue testing even if inserts fail
            }

            $endTime = microtime(true);
            $executionTimes[] = ($endTime - $startTime) * 1000;
        }

        return [
            'average_time' => array_sum($executionTimes) / count($executionTimes),
            'min_time' => min($executionTimes),
            'max_time' => max($executionTimes),
            'iterations' => $iterations,
        ];
    }

    /**
     * Test update performance.
     */
    private function testUpdatePerformance(): array
    {
        $iterations = 100;
        $executionTimes = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            try {
                DB::table('test_performance')
                    ->where('id', $i + 1)
                    ->update(['value' => $i * 20, 'updated_at' => now()]);
            } catch (\Exception $e) {
                // Continue testing even if updates fail
            }

            $endTime = microtime(true);
            $executionTimes[] = ($endTime - $startTime) * 1000;
        }

        return [
            'average_time' => array_sum($executionTimes) / count($executionTimes),
            'min_time' => min($executionTimes),
            'max_time' => max($executionTimes),
            'iterations' => $iterations,
        ];
    }

    /**
     * Test API endpoints performance.
     */
    private function testApiEndpointsPerformance(): array
    {
        $endpoints = [
            'GET /api/products' => function () {
                return $this->call('GET', '/api/products');
            },
            'GET /api/categories' => function () {
                return $this->call('GET', '/api/categories');
            },
            'POST /api/auth/login' => function () {
                return $this->call('POST', '/api/auth/login', [
                    'email' => 'test@example.com',
                    'password' => 'password',
                ]);
            },
        ];

        $results = [];
        foreach ($endpoints as $endpoint => $testFunction) {
            $results[$endpoint] = $this->measureApiPerformance($endpoint, $testFunction);
        }

        return $results;
    }

    /**
     * Measure API endpoint performance.
     */
    private function measureApiPerformance(string $endpoint, callable $testFunction): array
    {
        $iterations = 50;
        $executionTimes = [];
        $responseCodes = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            try {
                $response = $testFunction();
                $responseCodes[] = $response->getStatusCode();
            } catch (\Exception $e) {
                $responseCodes[] = 500;
            }

            $endTime = microtime(true);
            $executionTimes[] = ($endTime - $startTime) * 1000;
        }

        return [
            'average_response_time' => array_sum($executionTimes) / count($executionTimes),
            'min_response_time' => min($executionTimes),
            'max_response_time' => max($executionTimes),
            'success_rate' => count(array_filter($responseCodes, fn ($code) => $code < 400)) / count($responseCodes) * 100,
            'iterations' => $iterations,
        ];
    }

    /**
     * Test memory usage patterns.
     */
    private function testMemoryUsage(): array
    {
        $memoryTests = [
            'large_array_creation' => function () {
                $array = [];
                for ($i = 0; $i < 10000; $i++) {
                    $array[] = str_repeat('test', 100);
                }

                return $array;
            },
            'object_instantiation' => function () {
                $objects = [];
                for ($i = 0; $i < 1000; $i++) {
                    $objects[] = new \stdClass;
                }

                return $objects;
            },
            'string_manipulation' => function () {
                $string = '';
                for ($i = 0; $i < 1000; $i++) {
                    $string .= 'test_string_'.$i;
                }

                return $string;
            },
        ];

        $results = [];
        foreach ($memoryTests as $testName => $testFunction) {
            $startMemory = memory_get_usage();
            $peakMemory = memory_get_peak_usage();

            try {
                $testFunction();
            } catch (\Exception $e) {
                // Continue testing even if tests fail
            }

            $endMemory = memory_get_usage();
            $endPeakMemory = memory_get_peak_usage();

            $results[$testName] = [
                'memory_used' => $endMemory - $startMemory,
                'peak_memory_used' => $endPeakMemory - $peakMemory,
                'memory_efficiency' => $this->calculateMemoryEfficiency($startMemory, $endMemory),
            ];
        }

        return $results;
    }

    /**
     * Test concurrent users simulation.
     */
    private function testConcurrentUsers(): array
    {
        $concurrentUsers = [1, 5, 10, 20, 50];
        $results = [];

        foreach ($concurrentUsers as $userCount) {
            $results[$userCount.'_users'] = $this->simulateConcurrentUsers($userCount);
        }

        return $results;
    }

    /**
     * Simulate concurrent users.
     */
    private function simulateConcurrentUsers(int $userCount): array
    {
        $startTime = microtime(true);
        $responses = [];

        // Simulate concurrent requests
        for ($i = 0; $i < $userCount; $i++) {
            $requestStart = microtime(true);

            try {
                // Simulate API call
                $response = $this->call('GET', '/api/products');
                $responses[] = [
                    'status' => $response->getStatusCode(),
                    'response_time' => (microtime(true) - $requestStart) * 1000,
                ];
            } catch (\Exception $e) {
                $responses[] = [
                    'status' => 500,
                    'response_time' => (microtime(true) - $requestStart) * 1000,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        return [
            'total_time' => $totalTime,
            'average_response_time' => array_sum(array_column($responses, 'response_time')) / count($responses),
            'success_rate' => count(array_filter($responses, fn ($r) => $r['status'] < 400)) / count($responses) * 100,
            'requests_per_second' => $userCount / ($totalTime / 1000),
            'responses' => $responses,
        ];
    }

    /**
     * Calculate success rate.
     */
    private function calculateSuccessRate(array $executionTimes): float
    {
        $successfulCalls = count(array_filter($executionTimes, fn ($time) => $time < 5000)); // Less than 5 seconds

        return ($successfulCalls / count($executionTimes)) * 100;
    }

    /**
     * Calculate memory efficiency.
     */
    private function calculateMemoryEfficiency(int $startMemory, int $endMemory): float
    {
        $usedMemory = $endMemory - $startMemory;
        $maxAllowedMemory = 50 * 1024 * 1024; // 50MB

        return min(100, (1 - ($usedMemory / $maxAllowedMemory)) * 100);
    }

    /**
     * Generate performance report.
     */
    public function generatePerformanceReport(): array
    {
        $results = $this->runComprehensivePerformanceTests();

        return [
            'summary' => [
                'total_services_tested' => count($results['services']),
                'average_service_performance' => $this->calculateAverageServicePerformance($results['services']),
                'database_performance_score' => $this->calculateDatabasePerformanceScore($results['database']),
                'api_performance_score' => $this->calculateApiPerformanceScore($results['api_endpoints']),
                'memory_efficiency_score' => $this->calculateMemoryEfficiencyScore($results['memory_usage']),
                'concurrent_user_capacity' => $this->calculateConcurrentUserCapacity($results['concurrent_users']),
            ],
            'detailed_results' => $results,
            'recommendations' => $this->generatePerformanceRecommendations($results),
        ];
    }

    /**
     * Calculate average service performance.
     */
    private function calculateAverageServicePerformance(array $services): float
    {
        $totalScore = 0;
        $serviceCount = count($services);

        foreach ($services as $service) {
            $score = 100 - ($service['average_execution_time'] / 100); // Penalize slow services
            $totalScore += max(0, min(100, $score));
        }

        return $serviceCount > 0 ? $totalScore / $serviceCount : 0;
    }

    /**
     * Calculate database performance score.
     */
    private function calculateDatabasePerformanceScore(array $databaseResults): float
    {
        $scores = [];

        foreach ($databaseResults as $testType => $results) {
            if (is_array($results) && isset($results[0]['average_time'])) {
                foreach ($results as $result) {
                    $score = 100 - ($result['average_time'] / 10); // Penalize slow queries
                    $scores[] = max(0, min(100, $score));
                }
            } elseif (isset($results['average_time'])) {
                $score = 100 - ($results['average_time'] / 10);
                $scores[] = max(0, min(100, $score));
            }
        }

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    /**
     * Calculate API performance score.
     */
    private function calculateApiPerformanceScore(array $apiResults): float
    {
        $scores = [];

        foreach ($apiResults as $endpoint => $result) {
            $responseTimeScore = 100 - ($result['average_response_time'] / 50); // Penalize slow responses
            $successRateScore = $result['success_rate'];
            $score = ($responseTimeScore + $successRateScore) / 2;
            $scores[] = max(0, min(100, $score));
        }

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    /**
     * Calculate memory efficiency score.
     */
    private function calculateMemoryEfficiencyScore(array $memoryResults): float
    {
        $scores = [];

        foreach ($memoryResults as $testName => $result) {
            $scores[] = $result['memory_efficiency'];
        }

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    /**
     * Calculate concurrent user capacity.
     */
    private function calculateConcurrentUserCapacity(array $concurrentResults): int
    {
        $maxUsers = 0;

        foreach ($concurrentResults as $userCount => $result) {
            if ($result['success_rate'] >= 95 && $result['average_response_time'] <= 2000) {
                $maxUsers = (int) str_replace('_users', '', $userCount);
            }
        }

        return $maxUsers;
    }

    /**
     * Generate performance recommendations.
     */
    private function generatePerformanceRecommendations(array $results): array
    {
        $recommendations = [];

        // Service performance recommendations
        foreach ($results['services'] as $serviceName => $serviceResult) {
            if ($serviceResult['average_execution_time'] > 1000) {
                $recommendations[] = "Optimize {$serviceName}: Average execution time is {$serviceResult['average_execution_time']}ms";
            }
        }

        // Database performance recommendations
        if (isset($results['database']['complex_queries'])) {
            foreach ($results['database']['complex_queries'] as $query) {
                if ($query['average_time'] > 100) {
                    $recommendations[] = "Optimize complex query: {$query['query']} takes {$query['average_time']}ms";
                }
            }
        }

        // API performance recommendations
        foreach ($results['api_endpoints'] as $endpoint => $result) {
            if ($result['average_response_time'] > 500) {
                $recommendations[] = "Optimize API endpoint {$endpoint}: Average response time is {$result['average_response_time']}ms";
            }
        }

        // Memory recommendations
        foreach ($results['memory_usage'] as $testName => $result) {
            if ($result['memory_efficiency'] < 70) {
                $recommendations[] = "Optimize memory usage for {$testName}: Efficiency is {$result['memory_efficiency']}%";
            }
        }

        return $recommendations;
    }
}
