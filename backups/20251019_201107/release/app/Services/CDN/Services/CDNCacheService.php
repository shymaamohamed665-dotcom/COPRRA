<?php

declare(strict_types=1);

namespace App\Services\CDN\Services;

use App\Services\CDN\Contracts\CDNProviderInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling CDN cache operations
 */
final readonly class CDNCacheService
{
    private CDNProviderInterface $provider;

    public function __construct(CDNProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Purge CDN cache for URLs
     *
     * @param  list<string>  $urls  URLs to purge
     */
    public function purge(array $urls = []): bool
    {
        try {
            $result = $this->provider->purgeCache($urls);

            Log::info('CDN cache purged', [
                'urls' => $urls,
                'provider' => $this->provider->getName(),
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to purge CDN cache', [
                'urls' => $urls,
                'provider' => $this->provider->getName(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
