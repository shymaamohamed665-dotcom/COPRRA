<?php

declare(strict_types=1);

namespace App\Services\CDN\Contracts;

/**
 * Interface for CDN providers
 */
interface CDNProviderInterface
{
    /**
     * Upload content to CDN
     *
     * @param  string  $content  File content
     * @param  string  $path  Remote path
     * @param  string  $mimeType  MIME type
     * @return array<string, string|null>
     *
     * @throws \Exception
     */
    public function upload(string $content, string $path, string $mimeType): array;

    /**
     * Delete file from CDN
     *
     * @param  string  $path  Remote path
     *
     * @throws \Exception
     */
    public function delete(string $path): bool;

    /**
     * Purge cache for URLs
     *
     * @param  list<string>  $urls  URLs to purge
     *
     * @throws \Exception
     */
    public function purgeCache(array $urls): bool;

    /**
     * Get statistics from CDN
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function getStatistics(): array;

    /**
     * Test connection to CDN
     *
     * @throws \Exception
     */
    public function testConnection(): bool;

    /**
     * Get provider name
     */
    public function getName(): string;

    /**
     * Get base URL for CDN
     *
     * @param  string  $path  File path
     */
    public function getUrl(string $path): string;
}
