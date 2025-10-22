<?php

declare(strict_types=1);

namespace App\Services\Compression;

interface CompressionStrategyInterface
{
    /**
     * Check if this compression strategy is supported
     */
    public function isSupported(): bool;

    /**
     * Check if the client accepts this compression type
     */
    public function clientAccepts(string $acceptEncoding): bool;

    /**
     * Compress the content
     *
     * @return array{content: string, encoding: string}|null
     */
    public function compress(string $content): ?array;

    /**
     * Get the encoding name for this strategy
     */
    public function getEncoding(): string;
}
