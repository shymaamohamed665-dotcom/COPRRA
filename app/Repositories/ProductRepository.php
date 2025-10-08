<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use App\Services\Product\Services\ProductCacheService;
use App\Services\Product\Services\ProductPriceService;
use App\Services\Product\Services\ProductQueryBuilderService;
use App\Services\Product\Services\ProductValidationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Product Repository - Handles product data access with improved separation of concerns
 */
class ProductRepository
{
    private ProductValidationService $validationService;

    private ProductQueryBuilderService $queryBuilderService;

    private ProductCacheService $cacheService;

    private ProductPriceService $priceService;

    public function __construct()
    {
        $this->validationService = new ProductValidationService;
        $this->queryBuilderService = new ProductQueryBuilderService;
        $this->cacheService = new ProductCacheService;
        $this->priceService = new ProductPriceService;
    }

    /**
     * Get paginated active products
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedActive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->cacheService->rememberActiveProducts($perPage, function () use ($perPage) {
            return $this->queryBuilderService->buildActiveProductsQuery()->paginate($perPage);
        });
    }

    /**
     * Find product by slug with caching
     *
     * @throws InvalidArgumentException If slug is invalid
     */
    public function findBySlug(string $slug): ?Product
    {
        $this->validationService->validateSlug($slug);

        return $this->cacheService->rememberProductBySlug($slug, function () {
            return $this->queryBuilderService->buildProductBySlugQuery()->first();
        });
    }

    /**
     * Get related products with caching
     *
     * @return Collection<int, Product>
     *
     * @throws InvalidArgumentException If limit is invalid
     */
    public function getRelated(Product $product, int $limit = 4): Collection
    {
        $this->validationService->validateRelatedLimit($limit);

        return $this->cacheService->rememberRelatedProducts($product->id, $limit, function () use ($product, $limit) {
            return $this->queryBuilderService->buildRelatedQuery($product, $limit)->get();
        });
    }

    /**
     * Search products with validation and rate limiting
     *
     * @param  array<string, string|int|float>  $filters
     * @return LengthAwarePaginator<int, Product>
     *
     * @throws ValidationException If filters are invalid
     * @throws InvalidArgumentException If parameters are invalid
     */
    public function search(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $validated = $this->validationService->validateSearchParameters($query, $filters, $perPage);

        $page = is_numeric(request()->get('page', 1)) ? (int) request()->get('page', 1) : 1;

        return $this->cacheService->rememberSearch(
            $validated['query'],
            $validated['filters'],
            $validated['perPage'],
            $page,
            function () use ($validated) {
                return $this->queryBuilderService->buildSearchQuery(
                    $validated['query'],
                    $validated['filters']
                )->paginate($validated['perPage']);
            }
        );
    }

    /**
     * Update product price with validation, locking, and logging
     *
     * @throws ValidationException If price is invalid
     * @throws \RuntimeException If update fails
     */
    public function updatePrice(Product $product, float $newPrice): bool
    {
        $validatedPrice = $this->validationService->validatePrice($newPrice);

        $result = $this->priceService->updatePrice($product, $validatedPrice);

        // Invalidate relevant caches after successful update
        if ($result) {
            $this->cacheService->invalidateCaches([
                "product:{$product->id}",
                "product:slug:{$product->slug}:v1",
            ]);
        }

        return $result;
    }
}
