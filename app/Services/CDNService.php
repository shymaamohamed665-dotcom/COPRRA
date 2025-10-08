<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\CDN\Contracts\CDNProviderInterface;
use App\Services\CDN\Services\CDNCacheService;
use App\Services\CDN\Services\CDNFileService;
use App\Services\CDN\Services\CDNMonitorService;
use App\Services\CDN\Services\CDNProviderFactory;
use Exception;
use Illuminate\Support\Facades\Storage;

/**
 * CDN Service - Facade for CDN operations using provider pattern
 */
final class CDNService
{
    private CDNProviderInterface $provider;

    private CDNFileService $fileService;

    private CDNCacheService $cacheService;

    private CDNMonitorService $monitorService;

    /**
     * @var array<string, string|null>
     */
    private array $config;

    private readonly string $providerName;

    public function __construct()
    {
        $providerValue = config('cdn.provider', 'cloudflare');
        $this->providerName = is_string($providerValue) ? $providerValue : 'cloudflare';

        $configValue = config('cdn.providers.'.$this->providerName, []);
        /** @var array<string, string|null> $validConfig */
        $validConfig = is_array($configValue) ? $configValue : [];
        $this->config = $validConfig;

        $this->provider = CDNProviderFactory::create($this->providerName, $this->config);
        $this->fileService = new CDNFileService($this->provider);
        $this->cacheService = new CDNCacheService($this->provider);
        $this->monitorService = new CDNMonitorService($this->provider);
    }

    /**
     * Upload file to CDN
     *
     * @return array<string, string|null>
     *
     * @throws Exception
     */
    public function uploadFile(string $localPath, ?string $remotePath = null): array
    {
        $remotePath ??= $localPath;

        $fileContent = Storage::disk('public')->get($localPath);
        $mimeType = Storage::disk('public')->mimeType($localPath);
        if (! $mimeType) {
            $mimeType = 'application/octet-stream';
        }

        return $this->fileService->upload($fileContent ?? '', $remotePath, $mimeType);
    }

    /**
     * Upload multiple files to CDN
     *
     * @param  array<string, string>  $files
     * @return array<string, array<string, string|bool|null>>
     */
    public function uploadMultipleFiles(array $files): array
    {
        $results = [];

        foreach ($files as $localPath => $remotePath) {
            try {
                $results[$localPath] = $this->uploadFile($localPath, $remotePath);
            } catch (Exception $exception) {
                $results[$localPath] = [
                    'error' => $exception->getMessage(),
                    'success' => false,
                ];
            }
        }

        return $results;
    }

    /**
     * Delete file from CDN
     */
    public function deleteFile(string $remotePath): bool
    {
        return $this->fileService->delete($remotePath);
    }

    /**
     * Purge CDN cache
     *
     * @param  list<string>  $urls
     */
    public function purgeCache(array $urls = []): bool
    {
        return $this->cacheService->purge($urls);
    }

    /**
     * Get CDN URL for a file
     */
    public function getUrl(string $path): string
    {
        return $this->provider->getUrl($path);
    }

    /**
     * Check if file exists on CDN
     */
    public function fileExists(string $remotePath): bool
    {
        return $this->fileService->exists($remotePath);
    }

    /**
     * Get file metadata from CDN
     *
     * @return array<string, string|null>
     */
    public function getFileMetadata(string $remotePath): array
    {
        return $this->fileService->getMetadata($remotePath);
    }

    /**
     * Get CDN statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return $this->monitorService->getStatistics();
    }

    /**
     * Test connection to CDN
     */
    public function testConnection(): bool
    {
        return $this->monitorService->testConnection();
    }
}
