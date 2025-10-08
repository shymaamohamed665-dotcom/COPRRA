<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductService
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly CacheService $cache
    ) {}

    /**
     * Get paginated active products.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, \App\Models\Product>
     */
    public function getPaginatedProducts(int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $pageNumber = is_numeric($page) ? (int) $page : 1;

        $result = $this->cache->remember(
            'products.page.'.$pageNumber,
            3600,
            fn (): \Illuminate\Pagination\LengthAwarePaginator => $this->repository->getPaginatedActive($perPage),
            ['products']
        );

        // Return empty paginator if result is null
        if ($result === null) {
            return new LengthAwarePaginator(
                new Collection,
                0,
                $perPage,
                $pageNumber,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }

        return $result;
    }

    /**
     * Get product by slug.
     */
    public function getBySlug(string $slug): ?Product
    {
        return $this->cache->remember(
            'product.slug.'.$slug,
            3600,
            function () use ($slug): ?Product {
                return $this->repository->findBySlug($slug);
            },
            ['products']
        );
    }

    /**
     * Get related products.
     *
     * @return Collection<int, Product>
     */
    public function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        /** @var Collection<int, Product>|null $result */
        $result = $this->cache->remember(
            'product.related.'.$product->id,
            3600,
            fn (): Collection => $this->repository->getRelated($product, $limit),
            ['products']
        );

        return $result instanceof Collection ? $result : new Collection;
    }

    /**
     * Search products.
     *
     * @param  array<string, string|int|float>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function searchProducts(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Don't cache search results as they're likely to be unique per user
        return $this->repository->search($query, $filters, $perPage);
    }
}
