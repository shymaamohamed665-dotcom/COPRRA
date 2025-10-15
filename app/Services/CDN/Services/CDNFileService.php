<?php

declare(strict_types=1);

namespace App\Services\CDN\Services;

use App\Services\CDN\Contracts\CDNProviderInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling CDN file operations
 */
final class CDNFileService
{
    private CDNProviderInterface $provider;

    public function __construct(CDNProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Upload file to CDN
     *
     * @param  string  $content  File content
     * @param  string  $remotePath  Remote path
     * @param  string  $mimeType  MIME type
     * @return array<string, string|null>
     *
     * @throws Exception
     */
    public function upload(string $content, string $remotePath, string $mimeType): array
    {
        try {
            $result = $this->provider->upload($content, $remotePath, $mimeType);

            Log::info('File uploaded to CDN', [
                'remote_path' => $remotePath,
                'provider' => $this->provider->getName(),
                'url' => $result['url'],
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to upload file to CDN', [
                'remote_path' => $remotePath,
                'provider' => $this->provider->getName(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete file from CDN
     *
     * @param  string  $remotePath  Remote path
     */
    public function delete(string $remotePath): bool
    {
        try {
            $result = $this->provider->delete($remotePath);

            Log::info('File deleted from CDN', [
                'remote_path' => $remotePath,
                'provider' => $this->provider->getName(),
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to delete file from CDN', [
                'remote_path' => $remotePath,
                'provider' => $this->provider->getName(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if file exists on CDN
     *
     * @param  string  $remotePath  Remote path
     */
    public function exists(string $remotePath): bool
    {
        try {
            $url = $this->provider->getUrl($remotePath);
            $response = Http::head($url);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to check file existence on CDN', [
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get file metadata from CDN
     *
     * @param  string  $remotePath  Remote path
     * @return string[]
     *
     * @psalm-return array{url?: string, size?: string, mime_type?: string, last_modified?: string, etag?: string, cache_control?: string}
     */
    public function getMetadata(string $remotePath): array
    {
        try {
            $url = $this->provider->getUrl($remotePath);
            $response = Http::head($url);

            if (! $response->successful()) {
                throw new Exception('File not found on CDN');
            }

            return [
                'url' => $url,
                'size' => $response->header('Content-Length'),
                'mime_type' => $response->header('Content-Type'),
                'last_modified' => $response->header('Last-Modified'),
                'etag' => $response->header('ETag'),
                'cache_control' => $response->header('Cache-Control'),
            ];
        } catch (Exception $e) {
            Log::error('Failed to get file metadata from CDN', [
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
