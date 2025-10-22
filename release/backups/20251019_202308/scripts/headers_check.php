<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Http\Response;

$response = new Response('OK', 200);

// Try various ways to set CSP
$response->header('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self';");
echo 'Method header() get: '.var_export($response->headers->get('Content-Security-Policy'), true).PHP_EOL;

$response->headers->set('Content-Security-Policy', "default-src 'self'");
echo 'HeaderBag set/get: '.var_export($response->headers->get('Content-Security-Policy'), true).PHP_EOL;

$response = $response->withHeaders(['Content-Security-Policy' => "default-src 'self'"]);
echo 'withHeaders get: '.var_export($response->headers->get('Content-Security-Policy'), true).PHP_EOL;
