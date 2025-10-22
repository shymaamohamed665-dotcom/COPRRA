<?php

declare(strict_types=1);

namespace Tests\TestUtilities;

class TestReportProcessor
{
    /**
     * Process test results for reporting.
     */
    public function processTestResults(array $testResults): array
    {
        $processed = [
            'summary' => $this->processSummary($testResults['summary'] ?? []),
            'unit_tests' => $this->processUnitTests($testResults['detailed_results']['unit_tests'] ?? []),
            'integration_tests' => $this->processIntegrationTests($testResults['detailed_results']['integration_tests'] ?? []),
            'performance_tests' => $this->processPerformanceTests($testResults['detailed_results']['performance_tests'] ?? []),
            'security_tests' => $this->processSecurityTests($testResults['detailed_results']['security_tests'] ?? []),
            'api_tests' => $this->processApiTests($testResults['detailed_results']['api_tests'] ?? []),
            'database_tests' => $this->processDatabaseTests($testResults['detailed_results']['database_tests'] ?? []),
            'error_handling_tests' => $this->processErrorHandlingTests($testResults['detailed_results']['error_handling_tests'] ?? []),
            'validation_tests' => $this->processValidationTests($testResults['detailed_results']['validation_tests'] ?? []),
            'coverage' => $this->processCoverage($testResults['summary']['coverage'] ?? []),
            'recommendations' => $this->processRecommendations($testResults['recommendations'] ?? []),
            'execution_metrics' => $this->processExecutionMetrics($testResults['execution_metrics'] ?? []),
        ];

        return $processed;
    }

    /**
     * Process summary data.
     */
    private function processSummary(array $summary): array
    {
        return [
            'total_tests' => $summary['total_tests'] ?? 0,
            'passed' => $summary['passed'] ?? 0,
            'failed' => $summary['failed'] ?? 0,
            'success_rate' => $summary['success_rate'] ?? 0,
            'coverage' => $summary['coverage'] ?? [],
            'performance_score' => $summary['performance_score'] ?? 0,
            'security_score' => $summary['security_score'] ?? 0,
            'integration_score' => $summary['integration_score'] ?? 0,
            'overall_score' => $this->calculateOverallScore($summary),
        ];
    }

    /**
     * Process unit tests data.
     */
    private function processUnitTests(array $unitTests): array
    {
        $processed = [
            'total_services' => count($unitTests),
            'services' => [],
            'summary' => [
                'total_tests' => 0,
                'passed' => 0,
                'failed' => 0,
                'success_rate' => 0,
            ],
        ];

        foreach ($unitTests as $serviceName => $serviceResults) {
            if (isset($serviceResults['passed']) && isset($serviceResults['failed'])) {
                $totalTests = $serviceResults['passed'] + $serviceResults['failed'];
                $successRate = $totalTests > 0 ? ($serviceResults['passed'] / $totalTests) * 100 : 0;

                $processed['services'][$serviceName] = [
                    'total_tests' => $totalTests,
                    'passed' => $serviceResults['passed'],
                    'failed' => $serviceResults['failed'],
                    'success_rate' => $successRate,
                    'performance_metrics' => $serviceResults['performance_metrics'] ?? [],
                    'security_checks' => $serviceResults['security_checks'] ?? [],
                ];

                $processed['summary']['total_tests'] += $totalTests;
                $processed['summary']['passed'] += $serviceResults['passed'];
                $processed['summary']['failed'] += $serviceResults['failed'];
            }
        }

        $processed['summary']['success_rate'] = $processed['summary']['total_tests'] > 0
            ? ($processed['summary']['passed'] / $processed['summary']['total_tests']) * 100
            : 0;

        return $processed;
    }

    /**
     * Process integration tests data.
     */
    private function processIntegrationTests(array $integrationTests): array
    {
        $processed = [
            'workflows' => [],
            'api_tests' => [],
            'database_tests' => [],
            'cache_tests' => [],
            'queue_tests' => [],
            'summary' => [
                'total_workflows' => 0,
                'successful_workflows' => 0,
                'workflow_success_rate' => 0,
                'total_tests' => 0,
                'passed_tests' => 0,
                'test_success_rate' => 0,
            ],
        ];

        foreach ($integrationTests as $category => $results) {
            if (isset($results['workflow_name'])) {
                // Workflow test
                $processed['workflows'][$category] = [
                    'name' => $results['workflow_name'],
                    'total_steps' => $results['total_steps'],
                    'passed_steps' => $results['passed_steps'],
                    'failed_steps' => $results['failed_steps'],
                    'success' => $results['workflow_success'],
                ];

                $processed['summary']['total_workflows']++;
                if ($results['workflow_success']) {
                    $processed['summary']['successful_workflows']++;
                }
            } else {
                // Regular integration test
                $processed[$category] = $results;
                $processed['summary']['total_tests'] += $results['total_tests'] ?? 0;
                $processed['summary']['passed_tests'] += $results['passed'] ?? 0;
            }
        }

        $processed['summary']['workflow_success_rate'] = $processed['summary']['total_workflows'] > 0
            ? ($processed['summary']['successful_workflows'] / $processed['summary']['total_workflows']) * 100
            : 0;

        $processed['summary']['test_success_rate'] = $processed['summary']['total_tests'] > 0
            ? ($processed['summary']['passed_tests'] / $processed['summary']['total_tests']) * 100
            : 0;

        return $processed;
    }

    /**
     * Process performance tests data.
     */
    private function processPerformanceTests(array $performanceTests): array
    {
        $processed = [
            'services' => [],
            'database' => [],
            'api_endpoints' => [],
            'memory_usage' => [],
            'concurrent_users' => [],
            'summary' => [
                'average_service_performance' => 0,
                'database_performance_score' => 0,
                'api_performance_score' => 0,
                'memory_efficiency_score' => 0,
                'concurrent_user_capacity' => 0,
            ],
        ];

        foreach ($performanceTests as $category => $results) {
            if (is_array($results)) {
                $processed[$category] = $results;
            }
        }

        return $processed;
    }

    /**
     * Process security tests data.
     */
    private function processSecurityTests(array $securityTests): array
    {
        $processed = [
            'categories' => [],
            'summary' => [
                'total_tests' => 0,
                'passed' => 0,
                'failed' => 0,
                'success_rate' => 0,
                'vulnerabilities_found' => 0,
                'security_score' => 0,
            ],
        ];

        foreach ($securityTests as $category => $results) {
            if (isset($results['passed']) && isset($results['failed'])) {
                $totalTests = $results['passed'] + $results['failed'];
                $successRate = $totalTests > 0 ? ($results['passed'] / $totalTests) * 100 : 0;

                $processed['categories'][$category] = [
                    'total_tests' => $totalTests,
                    'passed' => $results['passed'],
                    'failed' => $results['failed'],
                    'success_rate' => $successRate,
                    'vulnerabilities' => $results['vulnerabilities'] ?? [],
                ];

                $processed['summary']['total_tests'] += $totalTests;
                $processed['summary']['passed'] += $results['passed'];
                $processed['summary']['failed'] += $results['failed'];
                $processed['summary']['vulnerabilities_found'] += count($results['vulnerabilities'] ?? []);
            }
        }

        $processed['summary']['success_rate'] = $processed['summary']['total_tests'] > 0
            ? ($processed['summary']['passed'] / $processed['summary']['total_tests']) * 100
            : 0;

        $processed['summary']['security_score'] = $processed['summary']['success_rate'];

        return $processed;
    }

    /**
     * Process API tests data.
     */
    private function processApiTests(array $apiTests): array
    {
        $processed = [
            'endpoints' => [],
            'summary' => [
                'total_tests' => 0,
                'passed' => 0,
                'failed' => 0,
                'success_rate' => 0,
            ],
        ];

        foreach ($apiTests as $endpoint => $results) {
            if (isset($results['total_tests'])) {
                $processed['endpoints'][$endpoint] = $results;
                $processed['summary']['total_tests'] += $results['total_tests'];
                $processed['summary']['passed'] += $results['passed'];
                $processed['summary']['failed'] += $results['failed'];
            }
        }

        $processed['summary']['success_rate'] = $processed['summary']['total_tests'] > 0
            ? ($processed['summary']['passed'] / $processed['summary']['total_tests']) * 100
            : 0;

        return $processed;
    }

    /**
     * Process database tests data.
     */
    private function processDatabaseTests(array $databaseTests): array
    {
        return $databaseTests;
    }

    /**
     * Process error handling tests data.
     */
    private function processErrorHandlingTests(array $errorHandlingTests): array
    {
        return $errorHandlingTests;
    }

    /**
     * Process validation tests data.
     */
    private function processValidationTests(array $validationTests): array
    {
        return $validationTests;
    }

    /**
     * Process coverage data.
     */
    private function processCoverage(array $coverage): array
    {
        return [
            'overall_coverage' => $coverage['overall_coverage'] ?? 0,
            'line_coverage' => $coverage['line_coverage'] ?? 0,
            'function_coverage' => $coverage['function_coverage'] ?? 0,
            'class_coverage' => $coverage['class_coverage'] ?? 0,
            'method_coverage' => $coverage['method_coverage'] ?? 0,
            'meets_requirements' => $this->checkCoverageRequirements($coverage),
        ];
    }

    /**
     * Process recommendations.
     */
    private function processRecommendations(array $recommendations): array
    {
        return [
            'total' => count($recommendations),
            'by_priority' => $this->categorizeRecommendations($recommendations),
            'list' => $recommendations,
        ];
    }

    /**
     * Process execution metrics.
     */
    private function processExecutionMetrics(array $metrics): array
    {
        return [
            'total_execution_time' => $metrics['total_execution_time'] ?? 0,
            'total_memory_usage' => $metrics['total_memory_usage'] ?? 0,
            'peak_memory_usage' => $metrics['peak_memory_usage'] ?? 0,
            'execution_date' => $metrics['execution_date'] ?? now()->toISOString(),
            'efficiency_score' => $this->calculateEfficiencyScore($metrics),
        ];
    }

    /**
     * Calculate overall score.
     */
    private function calculateOverallScore(array $summary): float
    {
        $scores = [
            $summary['success_rate'] ?? 0,
            $summary['performance_score'] ?? 0,
            $summary['security_score'] ?? 0,
            $summary['integration_score'] ?? 0,
        ];

        return array_sum($scores) / count($scores);
    }

    /**
     * Check coverage requirements.
     */
    private function checkCoverageRequirements(array $coverage): bool
    {
        $requirements = TestConfiguration::getCoverageRequirements('standard');

        return ($coverage['overall_coverage'] ?? 0) >= $requirements['overall_coverage_min'] &&
            ($coverage['line_coverage'] ?? 0) >= $requirements['line_coverage_min'] &&
            ($coverage['function_coverage'] ?? 0) >= $requirements['function_coverage_min'] &&
            ($coverage['class_coverage'] ?? 0) >= $requirements['class_coverage_min'] &&
            ($coverage['method_coverage'] ?? 0) >= $requirements['method_coverage_min'];
    }

    /**
     * Categorize recommendations by priority.
     */
    private function categorizeRecommendations(array $recommendations): array
    {
        $categorized = [
            'high' => [],
            'medium' => [],
            'low' => [],
        ];

        foreach ($recommendations as $recommendation) {
            $priority = $recommendation['priority'] ?? 'medium';
            $categorized[$priority][] = $recommendation;
        }

        return $categorized;
    }

    /**
     * Calculate efficiency score.
     */
    private function calculateEfficiencyScore(array $metrics): float
    {
        // Example calculation, can be adjusted
        $timeScore = isset($metrics['total_execution_time']) ? max(0, 100 - $metrics['total_execution_time']) : 50;
        $memoryScore = isset($metrics['peak_memory_usage']) ? max(0, 100 - ($metrics['peak_memory_usage'] / 1024 / 1024)) : 50; // MB

        return ($timeScore + $memoryScore) / 2;
    }
}
