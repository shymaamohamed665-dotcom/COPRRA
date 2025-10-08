<?php

/**
 * Script to migrate PHPUnit test annotations to PHPUnit 12 format
 * Converts @test annotations to test_ prefix
 */

$directory = __DIR__ . '/tests/Feature';

function processDirectory($dir)
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $count = 0;
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            if (processFile($file->getPathname())) {
                $count++;
            }
        }
    }

    return $count;
}

function processFile($filePath)
{
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Pattern to match /** @test */ annotation followed by a public function
    $pattern = '/\n\s*\/\*\*\s*@test\s*\*\/\s*\n\s*public\s+function\s+(?!test_)(\w+)/';

    $content = preg_replace_callback($pattern, function ($matches) {
        $functionName = $matches[1];

        // If function starts with 'it_', convert to 'test_'
        if (strpos($functionName, 'it_') === 0) {
            $newName = 'test_' . substr($functionName, 3);
        } else {
            $newName = 'test_' . $functionName;
        }

        // Return without the @test annotation
        return "\n    public function {$newName}";
    }, $content);

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Updated: $filePath\n";
        return true;
    }

    return false;
}

echo "Migrating tests to PHPUnit 12 format...\n";
$count = processDirectory($directory);
echo "Completed! Updated $count files.\n";
