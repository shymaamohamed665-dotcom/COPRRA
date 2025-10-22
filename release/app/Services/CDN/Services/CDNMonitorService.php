<?php

declare(strict_types=1);

namespace App\Services\CDN\Services;

use App\Services\CDN\Contracts\CDNProviderInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling CDN monitoring and statistics
 */
final readonly class CDNMonitorService
{
    private CDNProviderInterface $provider;

    public function __construct(CDNProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get CDN statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        try {
            return $this->provider->getStatistics();
        } catch (Exception $e) {
            Log::error('Failed to get CDN statistics', [
                'provider' => $this->provider->getName(),
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Test connection to CDN
     */
    public function testConnection(): bool
    {
        try {
            $result = $this->provider->testConnection();

            Log::info('CDN connection test completed', [
                'provider' => $this->provider->getName(),
                'success' => $result,
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('CDN connection test failed', [
                'provider' => $this->provider->getName(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
