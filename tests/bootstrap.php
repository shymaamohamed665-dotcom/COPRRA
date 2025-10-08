<?php

use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/../bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

return $app;


// Restore error handlers to prevent risky test warnings
set_error_handler(null);
restore_error_handler();
