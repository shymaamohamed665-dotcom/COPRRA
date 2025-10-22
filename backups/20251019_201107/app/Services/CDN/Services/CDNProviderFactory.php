<?php

declare(strict_types=1);

namespace App\Services\CDN\Services;

use App\Services\CDN\Providers\CloudflareProvider;
use App\Services\CDN\Providers\GoogleCloudProvider;
use App\Services\CDN\Providers\S3Provider;
use Exception;

/**
 * Factory for creating CDN provider instances
 */
final class CDNProviderFactory
{
    /**
     * Create CDN provider instance
     *
     * @param  string  $provider  Provider name
     * @param  array<string, string|null>  $config  Configuration
     *
     * @throws Exception
     */
    public static function create(string $provider, array $config): CloudflareProvider|S3Provider|GoogleCloudProvider
    {
        return match ($provider) {
            'cloudflare' => new CloudflareProvider($config),
            's3' => new S3Provider($config),
            'google_cloud' => new GoogleCloudProvider($config),
            default => throw new Exception("Unsupported CDN provider: {$provider}"),
        };
    }
}
