<?php

/**
 * Quick test summary - Get results faster by running tests in parallel batches
 */

echo "Running quick test summary...\n\n";

// Get list of all test files
$testFiles = glob(__DIR__ . '/tests/Feature/**/*Test.php');
$testFiles = array_merge($testFiles, glob(__DIR__ . '/tests/Feature/*Test.php'));

$totalTests = count($testFiles);
$passed = 0;
$failed = 0;
$errors = 0;

echo "Found $totalTests test files\n";
echo "Running sample validation...\n\n";

// Test a representative sample
$sampleSize = min(20, $totalTests);
$sample = array_rand(array_flip($testFiles), $sampleSize);

foreach ($sample as $index => $file) {
    $relativePath = str_replace(__DIR__ . '/', '', $file);

    $cmd = "php -d memory_limit=4G vendor/bin/phpunit --no-coverage " . escapeshellarg($file) . " 2>&1";
    exec($cmd, $output, $returnCode);

    $output = implode("\n", $output);

    if ($returnCode === 0) {
        echo "✓ ";
        $passed++;
    } else if (strpos($output, 'Error') !== false || strpos($output, 'Exception') !== false) {
        echo "E ";
        $errors++;
    } else {
        echo "F ";
        $failed++;
    }

    if (($index + 1) % 10 === 0) {
        echo "\n";
    }

    // Clear output for next iteration
    $output = [];
}

echo "\n\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║              SAMPLE TEST RESULTS (1/$sampleSize)              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "Passed:  $passed\n";
echo "Failed:  $failed\n";
echo "Errors:  $errors\n";

$successRate = ($passed / $sampleSize) * 100;
echo "\nEstimated Success Rate: " . number_format($successRate, 1) . "%\n";

if ($successRate >= 95) {
    echo "\n🎉 EXCELLENT! Tests are mostly passing!\n";
} else if ($successRate >= 80) {
    echo "\n✓ GOOD! Most tests are passing.\n";
} else if ($successRate >= 60) {
    echo "\n⚠ FAIR! More fixes needed.\n";
} else {
    echo "\n❌ MORE WORK NEEDED! Significant fixes required.\n";
}
