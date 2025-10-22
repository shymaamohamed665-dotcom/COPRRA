<?php

declare(strict_types=1);

namespace App\Services\Product\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service for building product queries
 */
final class ProductQueryBuilderService
{
    /**
     * Build search query
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function buildSearchQuery(string $query, array $filters): Builder
    {
        $productsQuery = Product::query()
            ->select(['id', 'name', 'slug', 'price', 'image', 'category_id', 'brand_id', 'description'])
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
            ])
            ->where('is_active', true);

        // Apply search query
        if ($query !== '' && $query !== '0') {
            $productsQuery->where(function (Builder $q) use ($query): void {
                $searchTerm = '%'.addcslashes($query, '%_').'%';
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm);
            });
        }

        // Apply filters
        $productsQuery = $this->applyFilters($productsQuery, $filters);

        // Apply sorting
        return $this->applySorting($productsQuery, $filters['sort_by'] ?? 'latest');
    }

    /**
     * Build related products query
     *
     * @psalm-return Builder<Product>
     */
    public function buildRelatedQuery(Product $product, int $limit): Builder
    {
        return Product::query()
            ->select(['id', 'name', 'slug', 'price', 'image', 'category_id'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($limit);
    }

    /**
     * Build active products query
     *
     * @psalm-return Builder<Product>
     */
    public function buildActiveProductsQuery(): Builder
    {
        return Product::query()
            ->with(['category', 'brand'])
            ->where('is_active', true)
            ->latest();
    }

    /**
     * Build product by slug query
     *
     * @psalm-return Builder<Product>
     */
    public function buildProductBySlugQuery(string $slug): Builder
    {
        return Product::query()
            ->with(['category', 'brand', 'reviews'])
            ->where('slug', $slug)
            ->where('is_active', true);
    }

    /**
     * Apply filters to query
     *
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     *
     * @param  Builder<Product>  $query
     *
     * @psalm-return Builder<Product>
     */
    private function applySorting(Builder $query, string $sortBy): Builder
    {
        return match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->latest(),
        };
    }
}
