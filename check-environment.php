#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Environment Check Script
 *
 * This script checks the server environment and ensures all requirements are met.
 * Usage: php check-environment.php
 */

use App\Services\EnvironmentChecker;

// Autoload the application
require_once __DIR__.'/vendor/autoload.php';

// Create and run the environment checker
$checker = new EnvironmentChecker;
$checker->run();
