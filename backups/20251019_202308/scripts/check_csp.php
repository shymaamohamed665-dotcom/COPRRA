<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

/** @var \Illuminate\Contracts\Foundation\Application $app */
$request = \Illuminate\Http\Request::create('/test', 'GET');

$middleware = $app->make(\App\Http\Middleware\SecurityHeaders::class);

$response = $middleware->handle($request, function ($req) {
    return response('OK', 200);
});

echo 'Status: '.$response->getStatusCode()."\n";
foreach ($response->headers->all() as $name => $values) {
    echo $name.': '.implode(', ', $values)."\n";
}

echo 'CSP: ';
var_dump($response->headers->get('Content-Security-Policy'));
