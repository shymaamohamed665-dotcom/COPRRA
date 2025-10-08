<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class TestAnalysisService
{
    private static bool $coverageEnabled = false;

    public static function create(): self
    {
        self::$coverageEnabled = false;

        return new self;
    }

    public static function withCoverage(): self
    {
        self::$coverageEnabled = true;

        return new self;
    }

    /**
     * Run and analyze tests with optional coverage.
     *
     * @param  array<string>  $issues
     */
    public function analyze(array &$issues): int
    {
        return $this->runAndAnalyzeTests($issues);
    }

    /**
     * @param  array<string>  $issues
     */
    private function runAndAnalyzeTests(array &$issues): int
    {
        try {
            $command = $this->buildTestCommand();
            $process = $this->runTestProcess($command);
            $output = $process->getOutput();

            $score = $this->analyzeTestResults($process, $output, $issues);

            return $score + $this->analyzeCoverage($output, $issues);
        } catch (ProcessFailedException $e) {
            $this->handleTestProcessException($e, $issues);

            return 0;
        }
    }

    /**
     * Build test command.
     *
     * @return array<string>
     */
    private function buildTestCommand(): array
    {
        $command = ['./vendor/bin/pest'];
        if (self::$coverageEnabled) {
            $command[] = '--coverage';
        }

        return $command;
    }

    /**
     * Run test process.
     *
     * @param  array<string>  $command
     */
    private function runTestProcess(array $command): Process
    {
        $process = new Process($command);
        $process->setTimeout(1800); // Increased timeout to 30 mins for coverage
        $process->run();

        return $process;
    }

    /**
     * Analyze test results.
     *
     * @param  array<string>  $issues
     */
    private function analyzeTestResults(Process $process, string $output, array &$issues): int
    {
        if ($process->isSuccessful()) {
            return preg_match('/Tests:\s+.*?(\d+)\s+passed/', $output) ? 70 : 0;
        }

        $issues[] = 'Some tests failed or encountered errors.';

        return 0;
    }

    /**
     * Analyze coverage.
     *
     * @param  array<string>  $issues
     */
    private function analyzeCoverage(string $output, array &$issues): int
    {
        if (! self::$coverageEnabled) {
            return 0;
        }

        if (preg_match('/Lines:\s+(\d+\.\d+)%/', $output, $matches)) {
            $coverage = (float) $matches[1];

            $coverageScore = $coverage / 100 * 30;

            return (int) $coverageScore;
        }

        $issues[] = 'Code coverage information not available';

        return 0;
    }

    /**
     * Handle test process exception.
     *
     * @param  array<string>  $issues
     */
    private function handleTestProcessException(
        ProcessFailedException $exception,
        array &$issues
    ): void {
        $process = $exception->getProcess();

        if (method_exists($process, 'isTimeout') && $process->isTimeout()) {
            $issues[] = 'Test analysis failed: Timeout exceeded.';

            return;
        }

        $issues[] = 'Test analysis failed with an error.';
    }
}
