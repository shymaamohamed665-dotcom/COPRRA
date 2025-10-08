<?php

declare(strict_types=1);

namespace App\Services\CDN\Providers;

use App\Services\CDN\Contracts\CDNProviderInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare CDN Provider Implementation
 */
final class CloudflareProvider implements CDNProviderInterface
{
    private string $apiToken;

    private string $accountId;

    private string $zoneId;

    private string $baseUrl;

    /**
     * @param  array<string, string|null>  $config
     */
    public function __construct(array $config)
    {
        $this->apiToken = (string) ($config['api_token'] ?? '');
        $this->accountId = (string) ($config['account_id'] ?? '');
        $this->zoneId = (string) ($config['zone_id'] ?? '');
        $this->baseUrl = (string) ($config['base_url'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function upload(string $content, string $path, string $mimeType): array
    {
        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1/{$path}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
            'Content-Type' => $mimeType,
        ])->put($url, ['content' => $content]);

        if (! $response->successful()) {
            throw new Exception('Cloudflare upload failed: '.$response->body());
        }

        $data = $response->json() ?? [];
        $result = isset($data['result']) && is_array($data['result']) ? $data['result'] : [];
        $variants = isset($result['variants']) && is_array($result['variants']) ? $result['variants'] : [];
        $imageId = $result['id'] ?? null;

        $url = is_string($variants[0] ?? null) ? $variants[0] : $this->getUrl($path);

        return [
            'url' => $url,
            'id' => $imageId,
            'provider' => 'cloudflare',
        ];
    }

    public function delete(string $path): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
        ])->delete('https://api.cloudflare.com/client/v4/accounts/'.$this->accountId."/images/v1/{$path}");

        return $response->successful();
    }

    /**
     * {@inheritdoc}
     */
    public function purgeCache(array $urls): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.cloudflare.com/client/v4/zones/'.$this->zoneId.'/purge_cache', [
            'purge_everything' => $urls === [],
            'files' => $urls,
        ]);

        return $response->successful();
    }

    /**
     * {@inheritdoc}
     */
    public function getStatistics(): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
        ])->get('https://api.cloudflare.com/client/v4/zones/'.$this->zoneId.'/analytics/dashboard');

        if (! $response->successful()) {
            throw new Exception('Failed to get Cloudflare statistics');
        }

        /** @var array<string, mixed> $result */
        $result = $response->json();

        return is_array($result) ? $result : [];
    }

    public function testConnection(): bool
    {
        $testPath = 'test/connection.txt';
        $testContent = 'CDN connection test - '.now();

        try {
            $result = $this->upload($testContent, $testPath, 'text/plain');

            if (isset($result['url'])) {
                $this->delete($testPath);

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Cloudflare connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getName(): string
    {
        return 'cloudflare';
    }

    public function getUrl(string $path): string
    {
        return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
    }
}
