<?php

declare(strict_types=1);

namespace App\Services\Compression;

class BrotliCompressionStrategy implements CompressionStrategyInterface
{
    private const COMPRESSION_LEVEL = 6;

    #[\Override]
    public function isSupported(): bool
    {
        return function_exists('brotli_compress');
    }

    #[\Override]
    public function clientAccepts(string $acceptEncoding): bool
    {
        return str_contains($acceptEncoding, 'br');
    }

    #[\Override]
    public function compress(string $content): ?array
    {
        if (! $this->isSupported()) {
            return null;
        }

        $compressed = brotli_compress($content, self::COMPRESSION_LEVEL);

        if ($compressed === false) {
            return null;
        }

        return [
            'content' => $compressed,
            'encoding' => $this->getEncoding(),
        ];
    }

    /**
     * @psalm-return 'br'
     */
    #[\Override]
    public function getEncoding(): string
    {
        return 'br';
    }
}
