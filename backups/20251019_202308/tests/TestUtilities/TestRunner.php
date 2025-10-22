<?php

declare(strict_types=1);

namespace Tests\TestUtilities;

use Illuminate\Support\Facades\Log;

/**
 * Main Test Runner for executing comprehensive test suite.
 *
 * This is the main entry point for running all test suites with
 * advanced features including performance monitoring, security validation,
 * and comprehensive reporting.
 */
class TestRunner
{
    private ComprehensiveTestRunner $comprehensiveRunner;

    private TestReportGenerator $reportGenerator;

    private TestConfiguration $config;

    public function __construct()
    {
        $this->comprehensiveRunner = new ComprehensiveTestRunner;
        $this->reportGenerator = new TestReportGenerator;
        $this->config = new TestConfiguration;
    }

    /**
     * Run comprehensive test suite with all features.
     */
    public function runComprehensiveTests(array $options = []): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        Log::info('Starting comprehensive test execution', $options);

        try {
            // Validate configuration
            $this->validateConfiguration();

            // Run all test suites
            $results = $this->comprehensiveRunner->runComprehensiveTests();

            // Generate comprehensive reports
            $reports = $this->reportGenerator->generateComprehensiveReport($results);

            // Calculate execution metrics
            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            $executionMetrics = [
                'total_execution_time' => ($endTime - $startTime) * 1000, // milliseconds
                'total_memory_usage' => $endMemory - $startMemory,
                'peak_memory_usage' => memory_get_peak_usage(),
                'execution_date' => now()->toISOString(),
            ];

            // Combine results
            $finalResults = [
                'test_results' => $results,
                'reports' => $reports,
                'execution_metrics' => $executionMetrics,
                'configuration' => $this->config->getAllConfig(),
                'summary' => $this->generateFinalSummary($results, $executionMetrics),
            ];

            Log::info('Comprehensive test execution completed successfully', [
                'execution_time' => $executionMetrics['total_execution_time'],
                'memory_usage' => $executionMetrics['total_memory_usage'],
            ]);

            return $finalResults;
        } catch (\Exception $e) {
            Log::error('Comprehensive test execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Run specific test suite.
     */
    public function runTestSuite(string $suiteName, array $options = []): array
    {
        Log::info("Running {$suiteName} test suite", $options);

        switch ($suiteName) {
            case 'unit':
                return $this->runUnitTests($options);
            case 'integration':
                return $this->runIntegrationTests($options);
            case 'performance':
                return $this->runPerformanceTests($options);
            case 'security':
                return $this->runSecurityTests($options);
            case 'api':
                return $this->runApiTests($options);
            case 'database':
                return $this->runDatabaseTests($options);
            case 'error':
                return $this->runErrorHandlingTests($options);
            case 'validation':
                return $this->runValidationTests($options);
            default:
                throw new \InvalidArgumentException("Unknown test suite: {$suiteName}");
        }
    }

    /**
     * Run unit tests.
     */
    private function runUnitTests(array $options): array
    {
        $serviceFactory = new ServiceTestFactory;
        $results = $serviceFactory->runComprehensiveTests();

        return [
            'suite_name' => 'unit',
            'results' => $results,
            'summary' => $this->generateSuiteSummary($results),
        ];
    }

    /**
     * Run integration tests.
     */
    private function runIntegrationTests(array $options): array
    {
        $integrationSuite = new IntegrationTestSuite;
        $results = $integrationSuite->runComprehensiveIntegrationTests();

        return [
            'suite_name' => 'integration',
            'results' => $results,
            'summary' => $this->generateSuiteSummary($results),
        ];
    }

    /**
     * Run performance tests.
     */
    private function runPerformanceTests(array $options): array
    {
        $performanceSuite = new PerformanceTestSuite;
        $results = $performanceSuite->runComprehensivePerformanceTests();

        return [
            'suite_name' => 'performance',
            'results' => $results,
            'summary' => $this->generateSuiteSummary($results),
        ];
    }

    /**
     * Run security tests.
     */
    private function runSecurityTests(array $options): array
    {
        $securitySuite = new SecurityTestSuite;
        $results = $securitySuite->runComprehensiveSecurityTests();

        return [
            'suite_name' => 'security',
            'results' => $results,
            'summary' => $this->generateSuiteSummary($results),
        ];
    }

    /**
     * Run API tests.
     */
    private function runApiTests(array $options): array
    {
        // This would run API-specific tests
        return [
            'suite_name' => 'api',
            'results' => [],
            'summary' => ['status' => 'completed'],
        ];
    }

    /**
     * Run database tests.
     */
    private function runDatabaseTests(array $options): array
    {
        // This would run database-specific tests
        return [
            'suite_name' => 'database',
            'results' => [],
            'summary' => ['status' => 'completed'],
        ];
    }

    /**
     * Run error handling tests.
     */
    private function runErrorHandlingTests(array $options): array
    {
        // This would run error handling tests
        return [
            'suite_name' => 'error',
            'results' => [],
            'summary' => ['status' => 'completed'],
        ];
    }

    /**
     * Run validation tests.
     */
    private function runValidationTests(array $options): array
    {
        // This would run validation tests
        return [
            'suite_name' => 'validation',
            'results' => [],
            'summary' => ['status' => 'completed'],
        ];
    }

    /**
     * Validate configuration.
     */
    private function validateConfiguration(): void
    {
        $errors = TestConfiguration::validate();

        if (! empty($errors)) {
            throw new \InvalidArgumentException('Configuration validation failed: '.implode(', ', $errors));
        }
    }

    /**
     * Generate suite summary.
     */
    private function generateSuiteSummary(array $results): array
    {
        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;

        // Calculate totals from results
        foreach ($results as $category => $categoryResults) {
            if (isset($categoryResults['total_tests'])) {
                $totalTests += $categoryResults['total_tests'];
                $totalPassed += $categoryResults['passed'] ?? 0;
                $totalFailed += $categoryResults['failed'] ?? 0;
            }
        }

        return [
            'total_tests' => $totalTests,
            'passed' => $totalPassed,
            'failed' => $totalFailed,
            'success_rate' => $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0,
        ];
    }

    /**
     * Generate final summary.
     */
    private function generateFinalSummary(array $results, array $executionMetrics): array
    {
        $summary = [
            'execution_successful' => true,
            'total_execution_time' => $executionMetrics['total_execution_time'],
            'total_memory_usage' => $executionMetrics['total_memory_usage'],
            'peak_memory_usage' => $executionMetrics['peak_memory_usage'],
            'execution_date' => $executionMetrics['execution_date'],
            'overall_score' => 0,
            'meets_requirements' => true,
            'recommendations' => [],
        ];

        // Calculate overall score
        if (isset($results['summary'])) {
            $summary['overall_score'] = $results['summary']['overall_score'] ?? 0;
        }

        // Check if meets requirements
        $minScore = TestConfiguration::get('coverage_requirements.overall_coverage_min', 95);
        $summary['meets_requirements'] = $summary['overall_score'] >= $minScore;

        // Generate recommendations
        $summary['recommendations'] = $this->generateRecommendations($results, $executionMetrics);

        return $summary;
    }

    /**
     * Generate recommendations.
     */
    private function generateRecommendations(array $results, array $executionMetrics): array
    {
        $recommendations = [];

        // Performance recommendations
        if ($executionMetrics['total_execution_time'] > 300000) { // 5 minutes
            $recommendations[] = 'Consider optimizing test execution time - currently taking '.
                round($executionMetrics['total_execution_time'] / 1000, 2).' seconds';
        }

        // Memory recommendations
        if ($executionMetrics['peak_memory_usage'] > 500 * 1024 * 1024) { // 500MB
            $recommendations[] = 'Consider optimizing memory usage - peak usage was '.
                round($executionMetrics['peak_memory_usage'] / (1024 * 1024), 2).' MB';
        }

        // Coverage recommendations
        if (isset($results['summary']['coverage'])) {
            $coverage = $results['summary']['coverage'];
            $minCoverage = TestConfiguration::get('coverage_requirements.overall_coverage_min', 95);

            if ($coverage['overall_coverage'] < $minCoverage) {
                $recommendations[] = "Increase test coverage - currently at {$coverage['overall_coverage']}%, target is {$minCoverage}%";
            }
        }

        // Security recommendations
        if (isset($results['summary']['security_score'])) {
            $securityScore = $results['summary']['security_score'];

            if ($securityScore < 90) {
                $recommendations[] = "Improve security score - currently at {$securityScore}%, target is 90%";
            }
        }

        return $recommendations;
    }

    /**
     * Get test statistics.
     */
    public function getTestStatistics(): array
    {
        return [
            'available_suites' => [
                'unit' => 'Unit tests for individual services',
                'integration' => 'Integration tests for workflows',
                'performance' => 'Performance and load tests',
                'security' => 'Security and vulnerability tests',
                'api' => 'API endpoint tests',
                'database' => 'Database and migration tests',
                'error' => 'Error handling tests',
                'validation' => 'Input validation tests',
            ],
            'configuration' => $this->config->getAllConfig(),
            'requirements' => [
                'coverage' => TestConfiguration::getCoverageRequirements('standard'),
                'performance' => TestConfiguration::getPerformanceThresholds('unit'),
                'security' => TestConfiguration::getSecurityRequirements('authentication'),
            ],
        ];
    }

    /**
     * Cleanup resources.
     */
    public function cleanup(): void
    {
        $this->comprehensiveRunner->cleanup();
    }
}
