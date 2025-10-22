<?php

declare(strict_types=1);

namespace App\Services\Compression;

class CompressionService
{
    /**
     * @var array<CompressionStrategyInterface>
     */
    private array $strategies = [];

    public function __construct()
    {
        $this->registerStrategies();
    }

    /**
     * Get the best compression strategy for the given request
     *
     * @return array{content: string, encoding: string}|null
     */
    public function compress(string $content, string $acceptEncoding): ?array
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->isSupported() && $strategy->clientAccepts($acceptEncoding)) {
                $result = $strategy->compress($content);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Check if client accepts any supported compression
     */
    public function clientAcceptsCompression(string $acceptEncoding): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->isSupported() && $strategy->clientAccepts($acceptEncoding)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register available compression strategies
     */
    private function registerStrategies(): void
    {
        $this->strategies[] = new BrotliCompressionStrategy;
        $this->strategies[] = new GzipCompressionStrategy;
    }
}
