<?php

declare(strict_types=1);

namespace App\Services\StoreClients;

use Illuminate\Support\Facades\Http;

abstract class BaseStoreClient
{
    protected string $apiKey;

    protected string $apiUrl;

    /**
     * @param  array<string, string>  $config
     */
    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiUrl = $config['api_url'] ?? '';
    }

    /**
     * @param  array<string, string|int|float|bool>  $filters
     * @return array<int, array<string, string|int|float|bool|array<string, string>>>
     */
    abstract public function search(string $query, array $filters): array;

    /**
     * @return array<string, string|int|float|bool|array<string, string>>|null
     */
    abstract public function getProduct(string $productId): ?array;

    abstract public function syncProducts(callable $syncCallback): void;

    /**
     * @return array<string, string|int|float>
     */
    public function getStatus(): array
    {
        try {
            $response = $this->makeRequest('get', '/health');

            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'response_time' => $response->transferStats?->getHandlerStat('total_time') ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string>>  $data
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [])
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Accept' => 'application/json',
        ])->timeout(10)->{$method}($this->apiUrl.$endpoint, $data);
    }
}
