<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';

$response = response('OK', 200);
// Use Illuminate response header API
$response->header('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self';");
echo 'CSP via get(): ';
var_dump($response->headers->get('Content-Security-Policy'));
echo "All headers:\n";
foreach ($response->headers->all() as $name => $values) {
    echo $name.': '.implode(', ', $values)."\n";
}
