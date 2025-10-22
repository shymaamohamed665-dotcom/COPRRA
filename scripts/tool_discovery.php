<?php
$root = __DIR__ . DIRECTORY_SEPARATOR . '..';
chdir($root);

function firstLine($str) {
    if ($str === null) return null;
    $str = trim($str);
    $lines = preg_split("/\r?\n/", $str);
    return $lines[0] ?? null;
}

function runCmd($cmd) {
    try {
        $out = @shell_exec($cmd);
        return firstLine($out);
    } catch (Throwable $e) {
        return null;
    }
}

function which($name) {
    if (stripos(PHP_OS, 'WIN') !== false) {
        return runCmd("where {$name}");
    }
    return runCmd("which {$name}");
}

$tools = [];
$tools['php'] = [
    'version' => runCmd('php -v'),
    'path' => which('php')
];
$tools['composer'] = [
    'version' => runCmd('composer -V'),
    'path' => which('composer')
];
$tools['phpunit'] = [
    'version' => runCmd('php vendor/bin/phpunit --version'),
    'path' => 'vendor/bin/phpunit'
];
$tools['phpstan'] = [
    'version' => runCmd('php vendor/bin/phpstan --version'),
    'path' => 'vendor/bin/phpstan'
];
$tools['psalm'] = [
    'version' => runCmd('php vendor/bin/psalm --version'),
    'path' => 'vendor/bin/psalm'
];
$tools['phpmd'] = [
    'version' => runCmd('php vendor/bin/phpmd --version'),
    'path' => 'vendor/bin/phpmd'
];
$tools['node'] = [
    'version' => runCmd('node -v'),
    'path' => which('node')
];
$tools['npm'] = [
    'version' => runCmd('npm -v'),
    'path' => which('npm')
];
$tools['eslint'] = [
    'version' => runCmd('npx eslint --version'),
    'path' => 'node_modules/eslint'
];
$tools['stylelint'] = [
    'version' => runCmd('npx stylelint --version'),
    'path' => 'node_modules/stylelint'
];
$tools['prettier'] = [
    'version' => runCmd('npx prettier --version'),
    'path' => 'node_modules/prettier'
];
$tools['docker'] = [
    'version' => runCmd('docker --version'),
    'path' => which('docker')
];
$tools['git'] = [
    'version' => runCmd('git --version'),
    'path' => which('git')
];

$file = $root . DIRECTORY_SEPARATOR . 'tool-discovery.json';
file_put_contents($file, json_encode($tools, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo json_encode($tools, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
