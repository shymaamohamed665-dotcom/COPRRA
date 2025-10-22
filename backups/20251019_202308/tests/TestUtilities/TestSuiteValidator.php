<?php

declare(strict_types=1);

namespace Tests\TestUtilities;

use Illuminate\Support\Facades\File;

/**
 * Test Suite Validator for comprehensive validation.
 *
 * This class provides validation capabilities for:
 * - Test configuration validation
 * - Test coverage validation
 * - Performance requirements validation
 * - Security requirements validation
 * - Test quality validation
 */
class TestSuiteValidator
{
    private TestConfiguration $config;

    public function __construct()
    {
        $this->config = new TestConfiguration;
    }

    /**
     * Validate complete test suite.
     */
    public function validateTestSuite(array $testResults): array
    {
        $validationResults = [
            'configuration' => $this->validateConfiguration(),
            'coverage' => $this->validateCoverage($testResults),
            'performance' => $this->validatePerformance($testResults),
            'security' => $this->validateSecurity($testResults),
            'quality' => $this->validateQuality($testResults),
            'overall' => [
                'valid' => true,
                'score' => 0,
                'issues' => [],
                'recommendations' => [],
            ],
        ];

        // Calculate overall validation
        $validationResults['overall'] = $this->calculateOverallValidation($validationResults);

        return $validationResults;
    }

    /**
     * Validate configuration.
     */
    private function validateConfiguration(): array
    {
        $errors = TestConfiguration::validate();

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => empty($errors) ? 100 : max(0, 100 - (count($errors) * 10)),
        ];
    }

    /**
     * Validate coverage requirements.
     */
    private function validateCoverage(array $testResults): array
    {
        $coverage = $testResults['summary']['coverage'] ?? [];
        $requirements = TestConfiguration::getCoverageRequirements('standard');

        $issues = [];
        $score = 100;

        // Check overall coverage
        if (($coverage['overall_coverage'] ?? 0) < $requirements['overall_coverage_min']) {
            $issues[] = "Overall coverage {$coverage['overall_coverage']}% is below required {$requirements['overall_coverage_min']}%";
            $score -= 20;
        }

        // Check line coverage
        if (($coverage['line_coverage'] ?? 0) < $requirements['line_coverage_min']) {
            $issues[] = "Line coverage {$coverage['line_coverage']}% is below required {$requirements['line_coverage_min']}%";
            $score -= 15;
        }

        // Check function coverage
        if (($coverage['function_coverage'] ?? 0) < $requirements['function_coverage_min']) {
            $issues[] = "Function coverage {$coverage['function_coverage']}% is below required {$requirements['function_coverage_min']}%";
            $score -= 15;
        }

        // Check class coverage
        if (($coverage['class_coverage'] ?? 0) < $requirements['class_coverage_min']) {
            $issues[] = "Class coverage {$coverage['class_coverage']}% is below required {$requirements['class_coverage_min']}%";
            $score -= 10;
        }

        // Check method coverage
        if (($coverage['method_coverage'] ?? 0) < $requirements['method_coverage_min']) {
            $issues[] = "Method coverage {$coverage['method_coverage']}% is below required {$requirements['method_coverage_min']}%";
            $score -= 10;
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'score' => max(0, $score),
            'requirements_met' => empty($issues),
        ];
    }

    /**
     * Validate performance requirements.
     */
    private function validatePerformance(array $testResults): array
    {
        $performance = $testResults['summary']['performance_score'] ?? 0;
        $executionMetrics = $testResults['execution_metrics'] ?? [];

        $issues = [];
        $score = 100;

        // Check overall performance score
        if ($performance < 80) {
            $issues[] = "Performance score {$performance}% is below acceptable threshold of 80%";
            $score -= 30;
        }

        // Check execution time
        $maxExecutionTime = TestConfiguration::get('performance_thresholds.unit_test_max_time', 100) * 100; // Convert to ms
        if (($executionMetrics['total_execution_time'] ?? 0) > $maxExecutionTime) {
            $issues[] = "Execution time {$executionMetrics['total_execution_time']}ms exceeds threshold {$maxExecutionTime}ms";
            $score -= 20;
        }

        // Check memory usage
        $maxMemoryUsage = TestConfiguration::get('performance_thresholds.memory_limit_mb', 50) * 1024 * 1024; // Convert to bytes
        if (($executionMetrics['peak_memory_usage'] ?? 0) > $maxMemoryUsage) {
            $issues[] = 'Peak memory usage '.round(($executionMetrics['peak_memory_usage'] ?? 0) / (1024 * 1024), 2).'MB exceeds threshold '.
                round($maxMemoryUsage / (1024 * 1024), 2).'MB';
            $score -= 20;
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'score' => max(0, $score),
            'performance_score' => $performance,
        ];
    }

    /**
     * Validate security requirements.
     */
    private function validateSecurity(array $testResults): array
    {
        $security = $testResults['summary']['security_score'] ?? 0;
        $securityTests = $testResults['detailed_results']['security_tests'] ?? [];

        $issues = [];
        $score = 100;

        // Check overall security score
        if ($security < 90) {
            $issues[] = "Security score {$security}% is below required threshold of 90%";
            $score -= 40;
        }

        // Check for vulnerabilities
        $totalVulnerabilities = 0;
        foreach ($securityTests as $category => $results) {
            if (isset($results['vulnerabilities'])) {
                $totalVulnerabilities += count($results['vulnerabilities']);
            }
        }

        if ($totalVulnerabilities > 0) {
            $issues[] = "Found {$totalVulnerabilities} security vulnerabilities";
            $score -= $totalVulnerabilities * 10;
        }

        // Check specific security requirements
        $securityRequirements = TestConfiguration::getSecurityRequirements('authentication');

        if ($security < $securityRequirements['min_password_strength']) {
            $issues[] = 'Password strength validation below required threshold';
            $score -= 15;
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'score' => max(0, $score),
            'vulnerabilities_found' => $totalVulnerabilities,
            'security_score' => $security,
        ];
    }

    /**
     * Validate test quality.
     */
    private function validateQuality(array $testResults): array
    {
        $issues = [];
        $score = 100;

        // Check test success rate
        $successRate = $testResults['summary']['success_rate'] ?? 0;
        if ($successRate < 95) {
            $issues[] = "Test success rate {$successRate}% is below required 95%";
            $score -= 25;
        }

        // Check for failed tests
        $failedTests = $testResults['summary']['failed'] ?? 0;
        if ($failedTests > 0) {
            $issues[] = "Found {$failedTests} failed tests";
            $score -= $failedTests * 5;
        }

        // Check test execution time
        $executionTime = $testResults['execution_metrics']['total_execution_time'] ?? 0;
        if ($executionTime > 600000) { // 10 minutes
            $issues[] = 'Test execution time is too long: '.round($executionTime / 1000, 2).' seconds';
            $score -= 15;
        }

        // Check memory efficiency
        $memoryUsage = $testResults['execution_metrics']['peak_memory_usage'] ?? 0;
        if ($memoryUsage > 200 * 1024 * 1024) { // 200MB
            $issues[] = 'Peak memory usage is too high: '.round($memoryUsage / (1024 * 1024), 2).'MB';
            $score -= 10;
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'score' => max(0, $score),
            'success_rate' => $successRate,
            'failed_tests' => $failedTests,
        ];
    }

    /**
     * Calculate overall validation.
     */
    private function calculateOverallValidation(array $validationResults): array
    {
        $scores = [
            $validationResults['configuration']['score'],
            $validationResults['coverage']['score'],
            $validationResults['performance']['score'],
            $validationResults['security']['score'],
            $validationResults['quality']['score'],
        ];

        $overallScore = array_sum($scores) / count($scores);
        $valid = $overallScore >= 80 &&
            $validationResults['configuration']['valid'] &&
            $validationResults['coverage']['valid'] &&
            $validationResults['security']['valid'];

        $issues = array_merge(
            $validationResults['configuration']['errors'] ?? [],
            $validationResults['coverage']['issues'] ?? [],
            $validationResults['performance']['issues'] ?? [],
            $validationResults['security']['issues'] ?? [],
            $validationResults['quality']['issues'] ?? []
        );

        $recommendations = $this->generateValidationRecommendations($validationResults);

        return [
            'valid' => $valid,
            'score' => $overallScore,
            'issues' => $issues,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Generate validation recommendations.
     */
    private function generateValidationRecommendations(array $validationResults): array
    {
        $recommendations = [];

        // Configuration recommendations
        if (! $validationResults['configuration']['valid']) {
            $recommendations[] = 'Fix configuration issues: '.implode(', ', $validationResults['configuration']['errors']);
        }

        // Coverage recommendations
        if (! $validationResults['coverage']['valid']) {
            $recommendations[] = 'Increase test coverage to meet requirements';
        }

        // Performance recommendations
        if (! $validationResults['performance']['valid']) {
            $recommendations[] = 'Optimize test performance and execution time';
        }

        // Security recommendations
        if (! $validationResults['security']['valid']) {
            $recommendations[] = 'Address security vulnerabilities and improve security score';
        }

        // Quality recommendations
        if (! $validationResults['quality']['valid']) {
            $recommendations[] = 'Improve test quality and reduce failed tests';
        }

        return $recommendations;
    }

    /**
     * Validate test files exist.
     */
    public function validateTestFiles(): array
    {
        $testDirectories = [
            'tests/Unit/Services',
            'tests/Feature',
            'tests/Integration',
            'tests/Performance',
            'tests/Security',
        ];

        $results = [
            'valid' => true,
            'missing_directories' => [],
            'missing_files' => [],
            'total_test_files' => 0,
        ];

        foreach ($testDirectories as $directory) {
            if (! File::exists($directory)) {
                $results['missing_directories'][] = $directory;
                $results['valid'] = false;
            } else {
                $files = File::allFiles($directory);
                $testFiles = array_filter($files, fn ($file) => str_ends_with($file->getFilename(), 'Test.php'));
                $results['total_test_files'] += count($testFiles);
            }
        }

        return $results;
    }

    /**
     * Validate test configuration files.
     */
    public function validateTestConfiguration(): array
    {
        $configFiles = [
            'phpunit.xml',
            'tests/TestUtilities/TestConfiguration.php',
        ];

        $results = [
            'valid' => true,
            'missing_files' => [],
            'invalid_files' => [],
        ];

        foreach ($configFiles as $file) {
            if (! File::exists($file)) {
                $results['missing_files'][] = $file;
                $results['valid'] = false;
            } else {
                // Basic validation of file content
                $content = File::get($file);
                if (empty($content)) {
                    $results['invalid_files'][] = $file;
                    $results['valid'] = false;
                }
            }
        }

        return $results;
    }

    /**
     * Generate validation report.
     */
    public function generateValidationReport(array $testResults): array
    {
        $validation = $this->validateTestSuite($testResults);
        $testFiles = $this->validateTestFiles();
        $config = $this->validateTestConfiguration();

        return [
            'validation_summary' => [
                'overall_valid' => $validation['overall']['valid'],
                'overall_score' => $validation['overall']['score'],
                'total_issues' => count($validation['overall']['issues']),
                'total_recommendations' => count($validation['overall']['recommendations']),
            ],
            'detailed_validation' => $validation,
            'test_files_validation' => $testFiles,
            'configuration_validation' => $config,
            'generated_at' => now()->toISOString(),
        ];
    }
}
