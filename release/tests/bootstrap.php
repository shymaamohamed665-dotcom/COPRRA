<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

// Ensure Composer autoload is available
if (! file_exists(__DIR__.'/../vendor/autoload.php')) {
    fwrite(STDERR, "[bootstrap] Missing vendor/autoload.php. Run 'composer install' first.\n");
    exit(1);
}

// Default to testing environment BEFORE any autoload to affect vendor bootstraps
$_ENV['APP_ENV'] ??= 'testing';
$_SERVER['APP_ENV'] ??= 'testing';
$_ENV['BROADCAST_DRIVER'] ??= 'null';
$_SERVER['BROADCAST_DRIVER'] ??= 'null';
$_ENV['REDIS_CLIENT'] ??= 'predis';
$_SERVER['REDIS_CLIENT'] ??= 'predis';
$_ENV['CACHE_DRIVER'] ??= 'array';
$_SERVER['CACHE_DRIVER'] ??= 'array';

// Removed PhpParser\Node preloading to avoid nested vendor redeclare conflicts

require __DIR__.'/../vendor/autoload.php';

// Ensure base test case is loaded even if dev autoload is unavailable
require_once __DIR__.'/TestCase.php';
// Load cross-suite base unit test if dev autoload is unavailable
@require_once __DIR__.'/Unit/PureUnitTest.php';

// Minimal PSR-4 autoloader fallback for the Tests namespace when autoload-dev is unavailable
spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'Tests\\') === 0) {
        $relative = substr($class, strlen('Tests\\'));
        $path = __DIR__.'/'.str_replace('\\', '/', $relative).'.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

// Create the Laravel application instance
$app = require __DIR__.'/../bootstrap/app.php';

// Optional: keep timezone deterministic across tests
date_default_timezone_set('UTC');

// Do not bootstrap the Kernel here; tests will bootstrap per-case via CreatesApplication

// Notes:
// - phpunit.xml sets DB to in-memory sqlite and uses array drivers for cache/session/queue.
// - Avoid loading .env.testing here to prevent overriding phpunit.xml values.
// - Tests using RefreshDatabase/LazilyRefreshDatabase will manage migrations automatically.