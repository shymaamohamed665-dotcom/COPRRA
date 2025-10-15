<?php

declare(strict_types=1);

namespace App\Services\Compression;

class GzipCompressionStrategy implements CompressionStrategyInterface
{
    private const COMPRESSION_LEVEL = 6;

    #[\Override]
    public function isSupported(): bool
    {
        return function_exists('gzencode');
    }

    #[\Override]
    public function clientAccepts(string $acceptEncoding): bool
    {
        return str_contains($acceptEncoding, 'gzip');
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{content: string, encoding: 'gzip'}|null
     */
    #[\Override]
    public function compress(string $content): ?array
    {
        if (! $this->isSupported()) {
            return null;
        }

        $compressed = gzencode($content, self::COMPRESSION_LEVEL);

        if ($compressed === false) {
            return null;
        }

        return [
            'content' => $compressed,
            'encoding' => $this->getEncoding(),
        ];
    }

    /**
     * @psalm-return 'gzip'
     */
    #[\Override]
    public function getEncoding(): string
    {
        return 'gzip';
    }
}
