<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class RecommendationService
{
    /**
     * @return array<int, \App\Models\Product>
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getRecommendations(User $user, int $limit = 10): array
    {
        $cacheKey = "recommendations_user_{$user->id}";

        return Cache::remember($cacheKey, 3600, /**
         * @psalm-return array<int, mixed>
         */
            function () use ($user, $limit): array {
                $recommendations = $this->collectRecommendations($user, $limit);

                return $this->filterAndLimitRecommendations($recommendations, $user, $limit);
            });
    }

    /**
     * @return array<int, \App\Models\Product>
     */
    public function getSimilarProducts(Product $product, int $limit = 5): array
    {
        return Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @return array<int, \App\Models\Product>
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 5): array
    {
        $productIds = $this->getFrequentlyBoughtProductIds($product, $limit);

        return Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->all();
    }

    /**
     * Collect recommendations from different sources
     *
     * @psalm-return \Illuminate\Support\Collection<never, never>
     */
    private function collectRecommendations(User $user, int $limit): \Illuminate\Support\Collection
    {
        $recommendations = collect();

        // Collaborative Filtering
        $collaborativeRecs = $this->getCollaborativeRecommendations($user, $limit);
        $recommendations = $recommendations->merge($collaborativeRecs);

        // Content-Based Filtering
        $contentRecs = $this->getContentBasedRecommendations($user, $limit);
        $recommendations = $recommendations->merge($contentRecs);

        // Trending Products
        $trendingRecs = $this->getTrendingRecommendations($limit);

        return $recommendations->merge($trendingRecs);
    }

    /**
     * Filter recommendations and apply limit
     *
     * @return array<int, mixed>
     */
    private function filterAndLimitRecommendations(\Illuminate\Support\Collection $recommendations, User $user, int $limit): array
    {
        $purchasedProductIds = $this->getPurchasedProductIds($user);

        return $recommendations
            ->unique('id')
            ->reject(static function (mixed $product) use ($purchasedProductIds): bool {
                if (! is_object($product) || ! property_exists($product, 'id')) {
                    return true;
                }

                return in_array($product->id, $purchasedProductIds);
            })
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Get product IDs that are frequently bought together with the given product
     *
     * @return array<int, int>
     */
    private function getFrequentlyBoughtProductIds(Product $product, int $limit): array
    {
        return OrderItem::whereHas('order', function ($query) use ($product): void {
            $query->whereHas('items', function ($q) use ($product): void {
                $q->where('product_id', $product->id);
            });
        })
            ->where('product_id', '!=', $product->id)
            ->select('product_id')
            ->selectRaw('COUNT(*) as frequency')
            ->groupBy('product_id')
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->pluck('product_id')
            ->toArray();
    }

    /**
     * Get user preferences based on purchase history
     *
     * @return array<string, array<int, int>|array<string, float|null>>
     */
    private function getUserPreferences(User $user): array
    {
        $purchases = OrderItem::whereHas('order', static function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->with('product')
            ->get();

        if ($purchases->isEmpty()) {
            return [];
        }

        $categories = $this->extractCategoryPreferences($purchases);
        $brands = $this->extractBrandPreferences($purchases);
        $priceRange = $this->extractPriceRange($purchases);

        return [
            'categories' => $categories->keys()->toArray(),
            'brands' => $brands->keys()->toArray(),
            'price_range' => $priceRange,
        ];
    }

    /**
     * Extract category preferences from purchases
     *
     * @psalm-return \Illuminate\Support\Collection<int, int>
     */
    private function extractCategoryPreferences(\Illuminate\Support\Collection $purchases): \Illuminate\Support\Collection
    {
        return $purchases->groupBy(function (OrderItem $item): int {
            $product = $item->product;

            return $product && $product->category_id ? $product->category_id : 0;
        })
            ->map(function ($items): int {
                return $items->sum('quantity');
            })
            ->sortDesc()
            ->take(5);
    }

    /**
     * Extract brand preferences from purchases
     *
     * @psalm-return \Illuminate\Support\Collection<int, int>
     */
    private function extractBrandPreferences(\Illuminate\Support\Collection $purchases): \Illuminate\Support\Collection
    {
        return $purchases->groupBy(function (OrderItem $item): int {
            $product = $item->product;

            return $product && $product->brand_id ? $product->brand_id : 0;
        })
            ->map(function ($items): int {
                return $items->sum('quantity');
            })
            ->sortDesc()
            ->take(5);
    }

    /**
     * Extract price range from purchases
     *
     * @return (float|int|mixed|null)[]
     *
     * @psalm-return array{min: mixed, max: mixed, average: float|int|null}
     */
    private function extractPriceRange(\Illuminate\Support\Collection $purchases): array
    {
        $prices = $purchases->map(function (OrderItem $item): float {
            $product = $item->product;

            return $product ? $product->price : 0.0;
        });

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
            'average' => $prices->avg(),
        ];
    }

    /**
     * @return array<int, \App\Models\Product>
     */
    private function getCollaborativeRecommendations(User $user, int $limit): array
    {
        // Find users with similar purchase patterns
        $similarUsers = $this->findSimilarUsers($user);

        if ($similarUsers->isEmpty()) {
            return [];
        }

        $similarUserIds = $similarUsers->pluck('user_id')->toArray();

        // Get products purchased by similar users but not by current user
        $purchasedProductIds = $this->getPurchasedProductIds($user);

        return $this->getProductsBySimilarUsers($similarUserIds, $purchasedProductIds, $limit);
    }

    /**
     * Get products purchased by similar users but not by current user
     *
     * @param  array<int, int>  $similarUserIds
     * @param  array<int, int>  $purchasedProductIds
     * @return array<int, \App\Models\Product>
     */
    private function getProductsBySimilarUsers(array $similarUserIds, array $purchasedProductIds, int $limit): array
    {
        return Product::whereHas('orderItems.order', function ($query) use ($similarUserIds): void {
            $query->whereIn('user_id', $similarUserIds);
        })
            ->whereNotIn('id', $purchasedProductIds)
            ->withCount([
                'orderItems as purchase_count' => function ($q) use ($similarUserIds): void {
                    $q->whereHas('order', function ($o) use ($similarUserIds): void {
                        $o->whereIn('user_id', $similarUserIds);
                    });
                },
            ])
            ->orderByDesc('purchase_count')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @return array<int, \App\Models\Product>
     */
    private function getContentBasedRecommendations(User $user, int $limit): array
    {
        $userPreferences = $this->getUserPreferences($user);

        if (count($userPreferences) === 0) {
            return [];
        }

        $query = Product::query();

        // Apply filters based on user preferences
        $this->applyCategoryFilter($query, $userPreferences);
        $this->applyPriceRangeFilter($query, $userPreferences);
        $this->applyBrandFilter($query, $userPreferences);

        return $query
            ->where('is_active', true)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Apply category filter to query
     *
     * @param  array<string, array<int, int>|array<string, float|null>>  $userPreferences
     */
    private function applyCategoryFilter(\Illuminate\Database\Eloquent\Builder $query, array $userPreferences): void
    {
        $categories = $userPreferences['categories'] ?? null;
        if (is_array($categories) && count($categories) > 0) {
            $query->whereIn('category_id', $categories);
        }
    }

    /**
     * Apply price range filter to query
     *
     * @param  array<string, array<int, int>|array<string, float|null>>  $userPreferences
     */
    private function applyPriceRangeFilter(\Illuminate\Database\Eloquent\Builder $query, array $userPreferences): void
    {
        if (isset($userPreferences['price_range']) && is_array($userPreferences['price_range'])) {
            $minPrice = $userPreferences['price_range']['min'] ?? null;
            $maxPrice = $userPreferences['price_range']['max'] ?? null;

            if ($minPrice !== null && $maxPrice !== null) {
                $query->whereBetween('price', [$minPrice, $maxPrice]);
            }
        }
    }

    /**
     * Apply brand filter to query
     *
     * @param  array<string, array<int, int>|array<string, float|null>>  $userPreferences
     */
    private function applyBrandFilter(\Illuminate\Database\Eloquent\Builder $query, array $userPreferences): void
    {
        $brands = $userPreferences['brands'] ?? null;
        if (is_array($brands) && count($brands) > 0) {
            $query->whereIn('brand_id', $brands);
        }
    }

    /**
     * @return array<int, \App\Models\Product>
     */
    private function getTrendingRecommendations(int $limit): array
    {
        return Product::where('is_active', true)
            ->withCount([
                'orderItems as recent_purchases' => $this->getRecentPurchasesQuery(),
            ])
            ->orderByDesc('recent_purchases')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Get query for recent purchases (last 7 days)
     *
     * @psalm-return \Closure(\Illuminate\Database\Eloquent\Builder):void
     */
    private function getRecentPurchasesQuery(): \Closure
    {
        return static function (\Illuminate\Database\Eloquent\Builder $query): void {
            $query->whereHas('order', static function (\Illuminate\Database\Eloquent\Builder $q): void {
                $q->where('created_at', '>=', now()->subDays(7));
            });
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function findSimilarUsers(User $user): \Illuminate\Support\Collection
    {
        $userPurchases = $this->getUserPurchaseHistory($user);

        if ($userPurchases->isEmpty()) {
            return new \Illuminate\Support\Collection;
        }

        $userProductIds = $userPurchases->pluck('product_id')->toArray();

        return $this->querySimilarUsers($user->id, $userProductIds);
    }

    /**
     * Query for finding similar users based on common products
     *
     * @psalm-return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function querySimilarUsers(int $userId, array $productIds): \Illuminate\Support\Collection
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', '!=', $userId)
            ->whereIn('order_items.product_id', $productIds)
            ->select('orders.user_id')
            ->selectRaw('COUNT(DISTINCT order_items.product_id) as common_products')
            ->groupBy('orders.user_id')
            ->having('common_products', '>=', 2)
            ->orderBy('common_products', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function getSimilarUsersByProduct(Product $product): \Illuminate\Support\Collection
    {
        $userIds = OrderItem::whereHas('order', function ($query) use ($product): void {
            $query->where('product_id', $product->id);
        })->pluck('order.user_id')->unique();

        return User::whereIn('id', $userIds)->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, OrderItem>
     */
    private function getUserPurchaseHistory(User $user): \Illuminate\Support\Collection
    {
        return OrderItem::whereHas('order', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->select('product_id')
            ->distinct()
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function getPurchasedProductIds(User $user): array
    {
        return OrderItem::whereHas('order', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })->pluck('product_id')->toArray();
    }
}
