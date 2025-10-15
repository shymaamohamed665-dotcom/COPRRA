<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

use Illuminate\Http\Request;

$request = Request::create('/test', 'GET');

/** @var \App\Http\Middleware\SecurityHeaders $middleware */
$middleware = $app->make(\App\Http\Middleware\SecurityHeaders::class);
$response = $middleware->handle($request, function ($req) {
    return response('OK', 200);
});

echo "All headers:\n";
var_export($response->headers->all());
echo "\n\n";
echo 'CSP: '.var_export($response->headers->get('Content-Security-Policy'), true).PHP_EOL;
echo 'Has CSP: '.($response->headers->has('Content-Security-Policy') ? 'yes' : 'no').PHP_EOL;
echo 'Status: '.$response->getStatusCode().PHP_EOL;
