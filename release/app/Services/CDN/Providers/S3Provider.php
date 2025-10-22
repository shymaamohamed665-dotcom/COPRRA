<?php

declare(strict_types=1);

namespace App\Services\CDN\Providers;

use App\Services\CDN\Contracts\CDNProviderInterface;

/**
 * AWS S3 CDN Provider Implementation
 */
final readonly class S3Provider implements CDNProviderInterface
{
    private string $bucket;

    private string $region;

    private string $baseUrl;

    /**
     * @param  array<string, string|null>  $config
     */
    public function __construct(array $config)
    {
        $this->bucket = (string) ($config['bucket'] ?? '');
        $this->region = (string) ($config['region'] ?? '');
        $this->baseUrl = (string) ($config['base_url'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function upload(string $content, string $path, string $mimeType): array
    {
        // This would use AWS SDK in a real implementation
        $url = 'https://'.$this->bucket.'.s3.'.$this->region.".amazonaws.com/{$path}";

        return [
            'url' => $url,
            'provider' => 'aws_s3',
        ];
    }

    #[\Override]
    public function delete(string $path): bool
    {
        // This would use AWS SDK in a real implementation
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function purgeCache(array $urls): bool
    {
        // S3 doesn't have built-in cache purging
        // This would invalidate CloudFront if configured
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getStatistics(): array
    {
        // This would use AWS SDK in a real implementation
        return [];
    }

    #[\Override]
    public function testConnection(): bool
    {
        // This would use AWS SDK in a real implementation
        return true;
    }

    /**
     * @psalm-return 'aws_s3'
     */
    #[\Override]
    public function getName(): string
    {
        return 'aws_s3';
    }

    #[\Override]
    public function getUrl(string $path): string
    {
        if ($this->baseUrl !== '' && $this->baseUrl !== '0') {
            return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
        }

        return 'https://'.$this->bucket.'.s3.'.$this->region.".amazonaws.com/{$path}";
    }
}
