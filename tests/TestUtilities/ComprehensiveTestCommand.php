<?php

declare(strict_types=1);

namespace Tests\TestUtilities;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive Test Command for running all test suites.
 *
 * This command provides a single entry point for running all test suites including:
 * - Unit tests
 * - Integration tests
 * - Performance tests
 * - Security tests
 * - API tests
 * - Database tests
 * - Error handling tests
 * - Validation tests
 */
class ComprehensiveTestCommand extends Command
{
    protected $signature = 'test:comprehensive
                            {--suite=all : Test suite to run (all, unit, integration, performance, security, api, database, error, validation)}
                            {--coverage : Generate coverage report}
                            {--performance : Run performance tests}
                            {--security : Run security tests}
                            {--integration : Run integration tests}
                            {--report : Generate detailed report}
                            {--format=html : Report format (html, json, xml)}
                            {--output=storage/app/test-reports : Output directory for reports}
                            {--parallel : Run tests in parallel}
                            {--timeout=3600 : Test timeout in seconds}
                            {--memory=512M : Memory limit for tests}';

    protected $description = 'Run comprehensive test suite with all test types';

    private ComprehensiveTestRunner $testRunner;

    private TestReportGenerator $reportGenerator;

    public function __construct()
    {
        parent::__construct();
        $this->testRunner = new ComprehensiveTestRunner;
        $this->reportGenerator = new TestReportGenerator;
    }

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $this->info('Starting comprehensive test execution...');

        try {
            // Set up test environment
            $this->setupTestEnvironment();

            // Run tests based on options
            $testResults = $this->runTests();

            // Generate reports if requested
            if ($this->option('report')) {
                $this->generateReports($testResults);
            }

            // Display results
            $this->displayResults($testResults);

            // Return appropriate exit code
            return $this->getExitCode($testResults);
        } catch (\Exception $e) {
            $this->error('Test execution failed: '.$e->getMessage());
            Log::error('Comprehensive test execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Set up test environment.
     */
    private function setupTestEnvironment(): void
    {
        $this->info('Setting up test environment...');

        // Set memory limit
        $memoryLimit = $this->option('memory');
        ini_set('memory_limit', $memoryLimit);

        // Set time limit
        $timeout = (int) $this->option('timeout');
        set_time_limit($timeout);

        // Configure test environment
        $this->configureTestEnvironment();

        $this->info('Test environment configured successfully');
    }

    /**
     * Configure test environment.
     */
    private function configureTestEnvironment(): void
    {
        // Set test environment variables
        $envVars = TestConfiguration::getTestEnvironmentVariables();

        foreach ($envVars as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }

        // Configure Laravel for testing
        config(['app.env' => 'testing']);
        config(['database.default' => 'testing']);
        config(['cache.default' => 'array']);
        config(['queue.default' => 'sync']);
        config(['mail.default' => 'array']);
        config(['session.driver' => 'array']);
    }

    /**
     * Run tests based on options.
     */
    private function runTests(): array
    {
        $suite = $this->option('suite');

        switch ($suite) {
            case 'unit':
                return $this->runUnitTests();
            case 'integration':
                return $this->runIntegrationTests();
            case 'performance':
                return $this->runPerformanceTests();
            case 'security':
                return $this->runSecurityTests();
            case 'api':
                return $this->runApiTests();
            case 'database':
                return $this->runDatabaseTests();
            case 'error':
                return $this->runErrorHandlingTests();
            case 'validation':
                return $this->runValidationTests();
            case 'all':
            default:
                return $this->runAllTests();
        }
    }

    /**
     * Run all tests.
     */
    private function runAllTests(): array
    {
        $this->info('Running all test suites...');

        $results = $this->testRunner->runComprehensiveTests();

        $this->info('All test suites completed');

        return $results;
    }

    /**
     * Run unit tests.
     */
    private function runUnitTests(): array
    {
        $this->info('Running unit tests...');

        $exitCode = Artisan::call('test', [
            '--testsuite' => 'Unit',
            '--coverage' => $this->option('coverage'),
        ]);

        $this->info('Unit tests completed with exit code: '.$exitCode);

        return [
            'unit_tests' => [
                'exit_code' => $exitCode,
                'status' => $exitCode === 0 ? 'passed' : 'failed',
            ],
        ];
    }

    /**
     * Run integration tests.
     */
    private function runIntegrationTests(): array
    {
        $this->info('Running integration tests...');

        $integrationSuite = new IntegrationTestSuite;
        $results = $integrationSuite->runComprehensiveIntegrationTests();

        $this->info('Integration tests completed');

        return [
            'integration_tests' => $results,
        ];
    }

    /**
     * Run performance tests.
     */
    private function runPerformanceTests(): array
    {
        $this->info('Running performance tests...');

        $performanceSuite = new PerformanceTestSuite;
        $results = $performanceSuite->runComprehensivePerformanceTests();

        $this->info('Performance tests completed');

        return [
            'performance_tests' => $results,
        ];
    }

    /**
     * Run security tests.
     */
    private function runSecurityTests(): array
    {
        $this->info('Running security tests...');

        $securitySuite = new SecurityTestSuite;
        $results = $securitySuite->runComprehensiveSecurityTests();

        $this->info('Security tests completed');

        return [
            'security_tests' => $results,
        ];
    }

    /**
     * Run API tests.
     */
    private function runApiTests(): array
    {
        $this->info('Running API tests...');

        $exitCode = Artisan::call('test', [
            '--testsuite' => 'Feature',
            '--filter' => 'Api',
        ]);

        $this->info('API tests completed with exit code: '.$exitCode);

        return [
            'api_tests' => [
                'exit_code' => $exitCode,
                'status' => $exitCode === 0 ? 'passed' : 'failed',
            ],
        ];
    }

    /**
     * Run database tests.
     */
    private function runDatabaseTests(): array
    {
        $this->info('Running database tests...');

        $exitCode = Artisan::call('test', [
            '--testsuite' => 'Feature',
            '--filter' => 'Database',
        ]);

        $this->info('Database tests completed with exit code: '.$exitCode);

        return [
            'database_tests' => [
                'exit_code' => $exitCode,
                'status' => $exitCode === 0 ? 'passed' : 'failed',
            ],
        ];
    }

    /**
     * Run error handling tests.
     */
    private function runErrorHandlingTests(): array
    {
        $this->info('Running error handling tests...');

        $exitCode = Artisan::call('test', [
            '--testsuite' => 'Feature',
            '--filter' => 'Error',
        ]);

        $this->info('Error handling tests completed with exit code: '.$exitCode);

        return [
            'error_handling_tests' => [
                'exit_code' => $exitCode,
                'status' => $exitCode === 0 ? 'passed' : 'failed',
            ],
        ];
    }

    /**
     * Run validation tests.
     */
    private function runValidationTests(): array
    {
        $this->info('Running validation tests...');

        $exitCode = Artisan::call('test', [
            '--testsuite' => 'Feature',
            '--filter' => 'Validation',
        ]);

        $this->info('Validation tests completed with exit code: '.$exitCode);

        return [
            'validation_tests' => [
                'exit_code' => $exitCode,
                'status' => $exitCode === 0 ? 'passed' : 'failed',
            ],
        ];
    }

    /**
     * Generate reports.
     */
    private function generateReports(array $testResults): void
    {
        $this->info('Generating reports...');

        $outputDir = $this->option('output');
        $format = $this->option('format');

        // Set output directory for report generator
        $reportGenerator = new TestReportGenerator;
        $reportGenerator->setOutputDirectory($outputDir);

        // Generate reports
        $reports = $reportGenerator->generateComprehensiveReport($testResults);

        $this->info('Reports generated:');
        foreach ($reports as $format => $filename) {
            $this->line("  {$format}: {$filename}");
        }
    }

    /**
     * Display results.
     */
    private function displayResults(array $testResults): void
    {
        $this->info('Test Results Summary:');
        $this->line('');

        if (isset($testResults['summary'])) {
            $summary = $testResults['summary'];

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Tests', $summary['total_tests'] ?? 0],
                    ['Passed', $summary['passed'] ?? 0],
                    ['Failed', $summary['failed'] ?? 0],
                    ['Success Rate', number_format($summary['success_rate'] ?? 0, 2).'%'],
                    ['Overall Score', number_format($summary['overall_score'] ?? 0, 2).'%'],
                ]
            );
        }

        if (isset($testResults['summary']['coverage'])) {
            $coverage = $testResults['summary']['coverage'];

            $this->line('');
            $this->info('Coverage Results:');
            $this->table(
                ['Type', 'Coverage'],
                [
                    ['Overall', number_format($coverage['overall_coverage'] ?? 0, 2).'%'],
                    ['Line', number_format($coverage['line_coverage'] ?? 0, 2).'%'],
                    ['Function', number_format($coverage['function_coverage'] ?? 0, 2).'%'],
                    ['Class', number_format($coverage['class_coverage'] ?? 0, 2).'%'],
                    ['Method', number_format($coverage['method_coverage'] ?? 0, 2).'%'],
                ]
            );
        }

        if (isset($testResults['recommendations'])) {
            $recommendations = $testResults['recommendations'];

            if (! empty($recommendations)) {
                $this->line('');
                $this->warn('Recommendations:');
                foreach ($recommendations as $recommendation) {
                    $this->line("  - {$recommendation}");
                }
            }
        }
    }

    /**
     * Get exit code based on test results.
     */
    private function getExitCode(array $testResults): int
    {
        if (isset($testResults['summary'])) {
            $summary = $testResults['summary'];

            // Check if all tests passed
            if (($summary['failed'] ?? 0) > 0) {
                return 1;
            }

            // Check if success rate meets requirements
            $minSuccessRate = TestConfiguration::get('coverage_requirements.overall_coverage_min', 95);
            if (($summary['success_rate'] ?? 0) < $minSuccessRate) {
                return 1;
            }

            // Check if coverage meets requirements
            if (isset($summary['coverage'])) {
                $coverage = $summary['coverage'];
                $minCoverage = TestConfiguration::get('coverage_requirements.overall_coverage_min', 95);
                if (($coverage['overall_coverage'] ?? 0) < $minCoverage) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
