<?php

declare(strict_types=1);

require_once __DIR__.'/check-environment.php';

$checker = new EnvironmentChecker;
$checker->run();
