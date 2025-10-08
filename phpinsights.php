<?php

declare(strict_types=1);

return [
    'preset' => 'laravel',
    'exclude' => [
        'vendor',
        'storage',
        'node_modules',
        'tests',
        'database',
        'resources',
        'routes',
        'config',
        'bootstrap',
        'public',
        'docs',
        'build',
        'reports',
        'scripts',
        'dev-docker',
        'docker',
        '.git',
        '.github',
        '.vscode',
        '.devcontainer',
        '.husky',
        '.lighthouseci',
        '.marscode',
        '.qodo',
        '.sfdx',
        '.zencoder',
    ],
    'add' => [
        // Add custom insights here
    ],
    'remove' => [
        // Remove insights here
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineFunctions::class,
    ],
    'config' => [
        // Configuration for insights
        'PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff' => [
            'lineLimit' => 250,
        ],
    ],
];
