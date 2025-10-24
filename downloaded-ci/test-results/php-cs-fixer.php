<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__.'/../app')
    ->in(__DIR__.'/../config')
    ->in(__DIR__.'/../routes')
    ->in(__DIR__.'/../database');

return (new Config)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
