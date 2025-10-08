<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PriceAlert;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Review;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;

final class ReportService
{
    public function __construct(
        private readonly Product $productModel,
        private readonly PriceOffer $priceOfferModel,
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly DatabaseManager $dbManager
    ) {}

    /**
     * Generate a comprehensive performance report for a single product.
     *
     * @return array<string, array<string, float|int|string>|string>
     */
    public function generateProductPerformanceReport(
        int $productId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $endDate = $endDate ?? Carbon::now();
        $startDate = $startDate ?? $endDate->copy()->subDays(30);

        $product = $this->productModel->findOrFail($productId);

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'current_price' => $product->price,
                'category' => $product->category->name ?? 'N/A',
                'brand' => $product->brand->name ?? 'N/A',
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'price_analysis' => $this->getPriceAnalysis($productId, $startDate, $endDate),
            'offer_analysis' => $this->getOfferAnalysis($productId, $startDate, $endDate),
            'user_engagement' => $this->getUserEngagement($productId, $startDate, $endDate),
            'reviews_analysis' => $this->getReviewsAnalysis($productId, $startDate, $endDate),
        ];
    }

    /**
     * Generate user activity report.
     *
     * @return array<string, array<string, int|string>|string>
     */
    public function generateUserActivityReport(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate ??= now()->subMonth();
        $endDate ??= now();

        $user = $this->userModel->findOrFail($userId);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'activity_summary' => $this->getUserActivitySummary($userId, $startDate, $endDate),
            'wishlist_activity' => $this->getWishlistActivity($startDate, $endDate),
            'price_alerts' => $this->getPriceAlertsActivity($startDate, $endDate),
            'reviews_activity' => $this->getReviewsActivity($startDate, $endDate),
        ];
    }

    /**
     * Generate sales report.
     *
     * @return array{
     *     period: array<string, string>,
     *     total_offers: int,
     *     price_changes: array<string, float|int>,
     *     top_products: array<int, array<string, int|string|null>>,
     *     top_stores: array<int, int>,
     *     price_trends: array<int, array<string, string|float>>
     * }
     */
    public function generateSalesReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate ??= now()->subMonth();
        $endDate ??= now();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'total_offers' => PriceOffer::whereBetween('created_at', [$startDate, $endDate])->count(),
            'price_changes' => $this->getPriceChanges($startDate, $endDate),
            'top_products' => $this->getTopProducts($startDate, $endDate),
            'top_stores' => $this->getTopStores($startDate, $endDate),
            'price_trends' => $this->getPriceTrends($startDate, $endDate),
        ];
    }

    /**
     * Get price analysis for a product.
     *
     * @return array<string, array<string, float|int>|float|int>
     */
    private function getPriceAnalysis(int $productId, Carbon $startDate, Carbon $endDate): array
    {
        $offers = PriceOffer::where('product_id', $productId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        if ($offers->isEmpty()) {
            return [
                'total_offers' => 0,
                'price_range' => ['min' => 0, 'max' => 0],
                'average_price' => 0,
                'price_volatility' => 0,
            ];
        }

        return $this->calculatePriceStatistics($offers);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\PriceOffer>  $offers
     * @return array<string, array<string, float|int>|float|int>
     */
    private function calculatePriceStatistics(
        \Illuminate\Database\Eloquent\Collection $offers
    ): array {
        /** @var array<float> $prices */
        $prices = $offers->pluck('price')->map(static function ($price): float {
            return is_numeric($price) ? (float) $price : 0.0;
        })->toArray();
        if ($prices === []) {
            return [
                'total_offers' => 0,
                'price_range' => ['min' => 0, 'max' => 0],
                'average_price' => 0,
                'price_volatility' => 0,
            ];
        }

        $totalOffers = count($prices);
        $priceRange = ['min' => min($prices), 'max' => max($prices)];
        $averagePrice = array_sum($prices) / $totalOffers;
        $priceVolatility = $this->calculatePriceVolatility($prices, $averagePrice);

        return [
            'total_offers' => $totalOffers,
            'price_range' => $priceRange,
            'average_price' => round($averagePrice, 2),
            'price_volatility' => round($priceVolatility, 2),
        ];
    }

    /**
     * @param  array<float>  $prices
     */
    private function calculatePriceVolatility(array $prices, float $averagePrice): float
    {
        $variance = array_reduce(
            $prices,
            static function (float $carry, float $price) use ($averagePrice): float {
                return $carry + ($price - $averagePrice) ** 2;
            },
            0.0
        ) / count($prices);

        return sqrt($variance);
    }

    /**
     * @return array<string, int|array<int, int>>
     */
    private function getOfferAnalysis(int $productId, Carbon $startDate, Carbon $endDate): array
    {
        $offers = PriceOffer::where('product_id', $productId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('store')
            ->get();

        $storeCounts = $offers->groupBy('store_id')->map->count();
        $availabilityCounts = $offers->groupBy('is_available')->map->count();

        return [
            'total_offers' => $offers->count(),
            'unique_stores' => $storeCounts->count(),
            'available_offers' => $availabilityCounts->get('1', 0),
            'unavailable_offers' => $availabilityCounts->get('0', 0),
            'top_stores' => $storeCounts->sortDesc()->take(5)->toArray(),
        ];
    }

    /**
     * Get user engagement for a product.
     *
     * @return array<string, int>
     */
    private function getUserEngagement(int $productId, Carbon $startDate, Carbon $endDate): array
    {
        $wishlists = $this->countProductActivity(Wishlist::class, $productId, $startDate, $endDate);
        $priceAlerts = $this->countProductActivity(PriceAlert::class, $productId, $startDate, $endDate);
        $reviews = $this->countProductActivity(Review::class, $productId, $startDate, $endDate);

        return [
            'wishlist_adds' => $wishlists,
            'price_alerts' => $priceAlerts,
            'reviews' => $reviews,
            'total_engagement' => $wishlists + $priceAlerts + $reviews,
        ];
    }

    /**
     * Count product activity for a given model.
     */
    private function countProductActivity(string $modelClass, int $productId, Carbon $startDate, Carbon $endDate): int
    {
        return $modelClass::where('product_id', $productId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get reviews analysis for a product.
     *
     * @return array<string, int|array<int, int>>
     */
    private function getReviewsAnalysis(int $productId, Carbon $startDate, Carbon $endDate): array
    {
        $reviews = Review::where('product_id', $productId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [],
                'approved_reviews' => 0,
                'pending_reviews' => 0,
            ];
        }

        return $this->calculateReviewStats($reviews);
    }

    /**
     * Calculate review statistics.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review>  $reviews
     * @return array<string, int|array<int, int>>
     */
    private function calculateReviewStats(\Illuminate\Database\Eloquent\Collection $reviews): array
    {
        $ratings = $reviews->pluck('rating')->toArray();
        $ratingDistribution = array_count_values($ratings);

        return [
            'total_reviews' => $reviews->count(),
            'average_rating' => array_sum($ratings) / count($ratings),
            'rating_distribution' => $ratingDistribution,
            'approved_reviews' => $reviews->where('is_approved', true)->count(),
            'pending_reviews' => $reviews->where('is_approved', false)->count(),
        ];
    }

    /**
     * Get user activity summary.
     *
     * @return array<string, int>
     */
    private function getUserActivitySummary(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $wishlists = $this->countUserActivity(Wishlist::class, $userId, $startDate, $endDate);
        $priceAlerts = $this->countUserActivity(PriceAlert::class, $userId, $startDate, $endDate);
        $reviews = $this->countUserActivity(Review::class, $userId, $startDate, $endDate);

        return [
            'wishlist_adds' => $wishlists,
            'price_alerts_created' => $priceAlerts,
            'reviews_written' => $reviews,
            'total_activity' => $wishlists + $priceAlerts + $reviews,
        ];
    }

    /**
     * Count user activity for a given model.
     */
    private function countUserActivity(string $modelClass, int $userId, Carbon $startDate, Carbon $endDate): int
    {
        return $modelClass::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get wishlist activity.
     *
     * @return array<int, array<string, int|string|null>>
     */
    private function getWishlistActivity(Carbon $startDate, Carbon $endDate): array
    {
        $wishlists = Wishlist::with('product')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        /** @var array<int, array<string, int|string|null>> $result */
        $result = $wishlists->map(static function (Wishlist $wishlist): array {
            $createdAt = $wishlist->created_at;

            return [
                'user_id' => $wishlist->user_id,
                'product_id' => $wishlist->product_id,
                'product_name' => $wishlist->product->name,
                'created_at' => $createdAt ? $createdAt->toDateTimeString() : null,
            ];
        })->toArray();

        return $result;
    }

    /**
     * Get price alerts activity.
     *
     * @return array<int, array<string, float|int|string|null>>
     */
    private function getPriceAlertsActivity(Carbon $startDate, Carbon $endDate): array
    {
        $alerts = PriceAlert::with('product')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        /** @var array<int, array<string, float|int|string|null>> $result */
        $result = $alerts->map(static function (PriceAlert $alert): array {
            $createdAt = $alert->created_at;

            return [
                'user_id' => $alert->user_id,
                'product_id' => $alert->product_id,
                'product_name' => $alert->product->name,
                'target_price' => $alert->target_price,
                'created_at' => $createdAt ? $createdAt->toDateTimeString() : null,
            ];
        })->toArray();

        return $result;
    }

    /**
     * Get reviews activity.
     *
     * @return array<int, array<string, int|string|null>>
     */
    private function getReviewsActivity(Carbon $startDate, Carbon $endDate): array
    {
        $reviews = Review::with('product')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        /** @var array<int, array<string, int|string|null>> $result */
        $result = $reviews->map(static function (Review $review): array {
            $createdAt = $review->created_at;

            return [
                'user_id' => $review->user_id,
                'product_id' => $review->product_id,
                'product_name' => $review->product->name,
                'rating' => $review->rating,
                'comment' => $review->content,
                'created_at' => $createdAt ? $createdAt->toDateTimeString() : null,
            ];
        })->toArray();

        return $result;
    }

    /**
     * Get price changes.
     *
     * @return array<string, float|int>
     */
    private function getPriceChanges(Carbon $startDate, Carbon $endDate): array
    {
        $priceChanges = $this->countPriceChanges($startDate, $endDate);

        return [
            'total_changes' => $priceChanges,
            'average_daily_changes' => $priceChanges / $startDate->diffInDays($endDate),
        ];
    }

    /**
     * Count price changes from audit logs.
     */
    private function countPriceChanges(Carbon $startDate, Carbon $endDate): int
    {
        return $this->dbManager->table('audit_logs')
            ->where('event', 'updated')
            ->where('auditable_type', \App\Models\Product::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereJsonContains('metadata->reason', 'Updated from lowest price offer')
            ->count();
    }

    /**
     * Get top products.
     *
     * @return array<int, array<string, int|string|null>>
     */
    private function getTopProducts(Carbon $startDate, Carbon $endDate): array
    {
        /** @var array<int, array<string, int|string|null>> $products */
        $products = Product::withCount(['wishlists', 'priceAlerts', 'reviews'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('wishlists_count', 'desc')
            ->orderBy('price_alerts_count', 'desc')
            ->orderBy('reviews_count', 'desc')
            ->take(10)
            ->get();

        return $products->map(function (Product $product): array {
            return $this->formatTopProduct($product);
        })->toArray();
    }

    /**
     * Format top product data.
     *
     * @return array<string, int|string|null>
     */
    private function formatTopProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'wishlists_count' => $product->wishlists_count,
            'price_alerts_count' => $product->price_alerts_count,
            'reviews_count' => $product->reviews_count,
        ];
    }

    /**
     * Get product details.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     price: float,
     *     wishlists_count: int,
     *     price_alerts_count: int,
     *     reviews_count: int
     * }
     */
    private function getProductDetails(Product $product): array
    {
        return [
            'id' => $this->getProductProperty($product, 'id', 0),
            'name' => $this->getProductProperty($product, 'name', ''),
            'price' => $this->getProductProperty($product, 'price', 0.0),
            'wishlists_count' => $this->getProductProperty($product, 'wishlists_count', 0),
            'price_alerts_count' => $this->getProductProperty($product, 'price_alerts_count', 0),
            'reviews_count' => $this->getProductProperty($product, 'reviews_count', 0),
        ];
    }

    /**
     * Get product property with fallback value.
     */
    private function getProductProperty(Product $product, string $property, mixed $default): mixed
    {
        return property_exists($product, $property) ? $product->$property : $default;
    }

    /**
     * Get price trends.
     *
     * @return array<int, array<string, string|float>>
     */
    private function getPriceTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = $this->priceOfferModel->where('product_id', $productId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->select(
                $this->dbManager->raw('DATE(created_at) as date'),
                $this->dbManager->raw('AVG(price) as average_price')
            )
            ->groupBy('date')
            ->get();

        return $trends->map(function ($trend): array {
            return $this->formatTrendData($trend);
        })->toArray();
    }

    /**
     * Format trend data.
     *
     * @param  object{trend: string|null, average_price: float|null, date: string|null}  $trend
     * @return array<string, string|float>
     */
    private function formatTrendData(object $trend): array
    {
        $avgPrice = property_exists($trend, 'average_price') &&
            is_numeric($trend->average_price)
            ? (float) $trend->average_price
            : 0.0;

        return [
            'date' => property_exists($trend, 'date') && is_string($trend->date) ? $trend->date : '',
            'average_price' => round($avgPrice, 2),
        ];
    }

    /**
     * Calculate price trend.
     *
     * @param  array<float>  $prices
     */
    private function calculatePriceTrend(array $prices): string
    {
        if (count($prices) < 2) {
            return 'stable';
        }

        $percentage = $this->calculatePricePercentage($prices);

        return $this->determineTrendDirection($percentage);
    }

    /**
     * Calculate price percentage change.
     *
     * @param  array<float>  $prices
     */
    private function calculatePricePercentage(array $prices): float
    {
        $firstPrice = $prices[0];
        $lastPrice = end($prices);
        $change = $lastPrice - $firstPrice;

        return $change / $firstPrice * 100;
    }

    /**
     * Determine trend direction based on percentage.
     */
    private function determineTrendDirection(float $percentage): string
    {
        if ($percentage > 5) {
            return 'increasing';
        }

        if ($percentage < -5) {
            return 'decreasing';
        }

        return 'stable';
    }
}
