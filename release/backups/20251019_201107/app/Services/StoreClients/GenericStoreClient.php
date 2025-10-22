<?php

declare(strict_types=1);

namespace App\Services\StoreClients;

class GenericStoreClient extends BaseStoreClient
{
    /**
     * @param  array<string, string|int|float|bool>  $filters
     *
     * @return array<int, array<string, scalar|array|object|null>>
     */
    #[\Override]
    public function search(string $query, array $filters): array
    {
        $response = $this->makeRequest('get', '/search', [
            'q' => $query,
            'limit' => 20,
            'filters' => $filters,
        ]);

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        // Handle case where json() returns a Closure (in tests)
        if ($data instanceof \Closure) {
            $data = $data();
        }

        return is_array($data) ? ($data['products'] ?? []) : [];
    }

    /**
     * @return array<string, scalar|array|object|null>|null
     */
    #[\Override]
    public function getProduct(string $productId): ?array
    {
        $response = $this->makeRequest('get', "/products/{$productId}");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        // Handle case where json() returns a Closure (in tests)
        if ($data instanceof \Closure) {
            $data = $data();
        }

        return is_array($data) ? $data : null;
    }

    #[\Override]
    public function syncProducts(callable $syncCallback): void
    {
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->makeRequest('get', '/products', [
                'page' => $page,
                'limit' => 100,
            ]);

            if (! $response->successful()) {
                $hasMore = false;

                continue;
            }

            $data = $response->json();

            // Handle case where json() returns a Closure (in tests)
            if ($data instanceof \Closure) {
                $data = $data();
            }

            $data = is_array($data) ? $data : [];
            $products = $data['products'] ?? [];

            foreach ($products as $productData) {
                $syncCallback($productData);
            }

            $hasMore = ($data['has_more'] ?? false) === true;
            $page++;
        }
    }
}
