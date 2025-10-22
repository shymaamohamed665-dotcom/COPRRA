#!/usr/bin/env php
<?php

// Temporary PHPUnit wrapper script

require __DIR__.'/vendor/autoload.php';

// Try to load PHAR if Composer-installed PHPUnit is unavailable
if (! class_exists('PHPUnit\\TextUI\\Application') && ! class_exists('PHPUnit\\TextUI\\Command')) {
    if (file_exists(__DIR__.'/phpunit.phar')) {
        require __DIR__.'/phpunit.phar';
    }
}

// Normalize argv for consistent behavior
$_SERVER['argv'][0] = __FILE__;
if (isset($argv)) {
    array_shift($argv); // Remove script name from local $argv if present
}

// Prefer new Application API when available (PHPUnit >= 10)
if (class_exists('PHPUnit\\TextUI\\Application')) {
    $exitCode = (new PHPUnit\TextUI\Application)->run($_SERVER['argv']);
    exit($exitCode);
}

// Fallback to legacy Command API (PHPUnit < 10)
if (class_exists('PHPUnit\\TextUI\\Command')) {
    // Command::main() will handle process exit internally
    PHPUnit\TextUI\Command::main();
    // If execution continues, exit with success
    exit(0);
}

fwrite(STDERR, "PHPUnit not found. Ensure dev dependencies are installed or phpunit.phar is present.\n");
exit(1);
