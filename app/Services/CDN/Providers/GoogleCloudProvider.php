<?php

declare(strict_types=1);

namespace App\Services\CDN\Providers;

use App\Services\CDN\Contracts\CDNProviderInterface;

/**
 * Google Cloud CDN Provider Implementation
 */
final class GoogleCloudProvider implements CDNProviderInterface
{
    private string $bucket;

    private string $baseUrl;

    /**
     * @param  array<string, string|null>  $config
     */
    public function __construct(array $config)
    {
        $this->bucket = (string) ($config['bucket'] ?? '');
        $this->baseUrl = (string) ($config['base_url'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function upload(string $content, string $path, string $mimeType): array
    {
        // This would use Google Cloud SDK in a real implementation
        $url = 'https://storage.googleapis.com/'.$this->bucket."/{$path}";

        return [
            'url' => $url,
            'provider' => 'google_cloud',
        ];
    }

    public function delete(string $path): bool
    {
        // This would use Google Cloud SDK in a real implementation
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purgeCache(array $urls): bool
    {
        // This would use Google Cloud SDK in a real implementation
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatistics(): array
    {
        // This would use Google Cloud SDK in a real implementation
        return [];
    }

    public function testConnection(): bool
    {
        // This would use Google Cloud SDK in a real implementation
        return true;
    }

    public function getName(): string
    {
        return 'google_cloud';
    }

    public function getUrl(string $path): string
    {
        if (! empty($this->baseUrl)) {
            return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
        }

        return 'https://storage.googleapis.com/'.$this->bucket."/{$path}";
    }
}
