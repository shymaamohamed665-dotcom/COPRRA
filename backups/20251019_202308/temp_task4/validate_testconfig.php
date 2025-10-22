<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__.'/../tests/TestUtilities/TestConfiguration.php';

use Tests\TestUtilities\TestConfiguration;

$errors = TestConfiguration::validate();

if (empty($errors)) {
    echo 'OK: TestConfiguration::validate returned no errors'.PHP_EOL;
    exit(0);
}

echo 'FAIL: TestConfiguration::validate returned errors'.PHP_EOL;
foreach ($errors as $e) {
    echo '- '.$e.PHP_EOL;
}
exit(1);
