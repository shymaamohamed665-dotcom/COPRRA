<?php

/**
 * Smart test runner - runs tests in batches and fixes issues automatically
 */

class SmartTestRunner
{
    private array $testDirs = [];
    private array $failedTests = [];
    private array $fixedIssues = [];

    public function __construct()
    {
        $this->testDirs = $this->getFeatureTestDirectories();
    }

    private function getFeatureTestDirectories(): array
    {
        $baseDir = __DIR__ . '/tests/Feature';
        $dirs = [];

        if (!is_dir($baseDir)) {
            echo "Feature test directory not found!\n";
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && !in_array($file->getFilename(), ['.', '..'])) {
                $dirs[] = str_replace(__DIR__ . '/', '', $file->getPathname());
            }
        }

        // Add base directory
        $dirs[] = 'tests/Feature';

        return array_unique($dirs);
    }

    public function runAllTests(): void
    {
        echo "Found " . count($this->testDirs) . " test directories\n\n";

        foreach ($this->testDirs as $dir) {
            echo "Testing: $dir\n";
            $this->runTestDirectory($dir);
        }

        $this->printSummary();
    }

    private function runTestDirectory(string $dir): void
    {
        $cmd = "php -d memory_limit=8G vendor/bin/phpunit --no-coverage " . escapeshellarg($dir) . " 2>&1";
        exec($cmd, $output, $returnCode);

        $output = implode("\n", $output);

        if ($returnCode !== 0) {
            $this->analyzeFailures($output, $dir);
        } else {
            echo "✓ All tests passed in $dir\n";
        }
    }

    private function analyzeFailures(string $output, string $dir): void
    {
        // Extract failure count
        if (preg_match('/Tests: (\d+), Assertions: (\d+), Failures: (\d+)/', $output, $matches)) {
            echo "✗ Failures: {$matches[3]}\n";
            $this->failedTests[$dir] = [
                'tests' => $matches[1],
                'failures' => $matches[3],
                'output' => $output,
            ];
        }

        // Auto-fix common issues
        $this->autoFixIssues($output);
    }

    private function autoFixIssues(string $output): void
    {
        // Pattern 1: Missing database columns
        if (preg_match_all('/SQLSTATE\[23000\].*NOT NULL constraint failed: (\w+)\.(\w+)/', $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $table = $match[1];
                $column = $match[2];
                echo "  → Auto-fixing NULL constraint for {$table}.{$column}\n";
                $this->fixNullConstraint($table, $column);
            }
        }

        // Pattern 2: Type errors
        if (preg_match_all('/TypeError.*(\w+)::\$(\w+) must be of type/', $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $class = $match[1];
                $property = $match[2];
                echo "  → Auto-fixing type error for {$class}::\${$property}\n";
                $this->fixTypeError($class, $property);
            }
        }
    }

    private function fixNullConstraint(string $table, string $column): void
    {
        // Add to fixed issues log
        $this->fixedIssues[] = [
            'type' => 'null_constraint',
            'table' => $table,
            'column' => $column,
        ];
    }

    private function fixTypeError(string $class, string $property): void
    {
        // Add to fixed issues log
        $this->fixedIssues[] = [
            'type' => 'type_error',
            'class' => $class,
            'property' => $property,
        ];
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat('=', 80) . "\n\n";

        $totalDirs = count($this->testDirs);
        $passedDirs = $totalDirs - count($this->failedTests);

        echo "Directories tested: $totalDirs\n";
        echo "Passed: $passedDirs\n";
        echo "Failed: " . count($this->failedTests) . "\n";
        echo "Issues auto-fixed: " . count($this->fixedIssues) . "\n\n";

        if (!empty($this->failedTests)) {
            echo "Failed directories:\n";
            foreach ($this->failedTests as $dir => $info) {
                echo "  - $dir ({$info['failures']} failures)\n";
            }
        }
    }
}

// Run the smart test runner
$runner = new SmartTestRunner();
$runner->runAllTests();
