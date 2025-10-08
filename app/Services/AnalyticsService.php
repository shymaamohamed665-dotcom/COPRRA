<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AnalyticsService
{
    /**
     * Track an analytics event.
     *
     * @param  array<string, string|int|float|bool|null>|null  $metadata
     */
    public function track(
        string $eventType,
        string $eventName,
        ?int $userId = null,
        ?int $productId = null,
        ?int $categoryId = null,
        ?int $storeId = null,
        ?array $metadata = null
    ): ?AnalyticsEvent {
        if (! config('coprra.analytics.track_user_behavior', true)) {
            return null;
        }

        try {
            $ipAddress = null;
            $userAgent = null;
            $sessionId = null;

            // Safely get request data if available
            if (app()->bound('request')) {
                $request = app('request');
                $ipAddress = $request->ip();
                $userAgent = $request->userAgent();
            }

            // Safely get session data if available
            if (app()->bound('session')) {
                $session = app('session');
                $sessionId = $session->getId();
            }

            return AnalyticsEvent::create([
                'event_type' => $eventType,
                'event_name' => $eventName,
                'user_id' => $userId,
                'product_id' => $productId,
                'category_id' => $categoryId,
                'store_id' => $storeId,
                'metadata' => $metadata,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'session_id' => $sessionId,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Failed to track analytics event', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Track price comparison event.
     *
     * @param  array<string, string|int|float|bool|null>  $metadata
     */
    public function trackPriceComparison(int $productId, ?int $userId = null, array $metadata = []): ?AnalyticsEvent
    {
        return $this->track(
            AnalyticsEvent::TYPE_PRICE_COMPARISON,
            'Price Comparison Viewed',
            $userId,
            $productId,
            null,
            null,
            $metadata
        );
    }

    /**
     * Track product view event.
     */
    public function trackProductView(int $productId, ?int $userId = null): ?AnalyticsEvent
    {
        return $this->track(
            AnalyticsEvent::TYPE_PRODUCT_VIEW,
            'Product Viewed',
            $userId,
            $productId
        );
    }

    /**
     * Track search event.
     *
     * @param  array<string, string|int|float|bool|null>  $filters
     */
    public function trackSearch(string $query, ?int $userId = null, array $filters = []): ?AnalyticsEvent
    {
        return $this->track(
            AnalyticsEvent::TYPE_SEARCH,
            'Search Performed',
            $userId,
            null,
            null,
            null,
            ['query' => $query, 'filters' => $filters]
        );
    }

    /**
     * Track store click event.
     */
    public function trackStoreClick(int $storeId, int $productId, ?int $userId = null): ?AnalyticsEvent
    {
        return $this->track(
            AnalyticsEvent::TYPE_STORE_CLICK,
            'Store Clicked',
            $userId,
            $productId,
            null,
            $storeId
        );
    }

    /**
     * Get most viewed products.
     *
     * @return array<int, array<string, string|int|null>>
     */
    public function getMostViewedProducts(int $limit = 10, int $days = 30): array
    {
        return AnalyticsEvent::ofType(AnalyticsEvent::TYPE_PRODUCT_VIEW)
            ->recent($days)
            ->select('product_id', DB::raw('COUNT(*) as view_count'))
            ->groupBy('product_id')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->with('product')
            ->get()
            ->toArray();
    }

    /**
     * Get most searched queries.
     *
     * @return array<string, int>
     */
    public function getMostSearchedQueries(int $limit = 10, int $days = 30): array
    {
        return AnalyticsEvent::ofType(AnalyticsEvent::TYPE_SEARCH)
            ->recent($days)
            ->get()
            ->pluck('metadata.query')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->toArray();
    }

    /**
     * Get most popular stores.
     *
     * @return array<int, array<string, string|int|null>>
     */
    public function getMostPopularStores(int $limit = 10, int $days = 30): array
    {
        return AnalyticsEvent::ofType(AnalyticsEvent::TYPE_STORE_CLICK)
            ->recent($days)
            ->select('store_id', DB::raw('COUNT(*) as click_count'))
            ->groupBy('store_id')
            ->orderByDesc('click_count')
            ->limit($limit)
            ->with('store')
            ->get()
            ->toArray();
    }

    /**
     * Get price comparison statistics.
     *
     * @return array<string, int|float>
     */
    public function getPriceComparisonStats(int $days = 30): array
    {
        $events = AnalyticsEvent::ofType(AnalyticsEvent::TYPE_PRICE_COMPARISON)
            ->recent($days)
            ->get();

        return [
            'total_comparisons' => $events->count(),
            'unique_products' => $events->pluck('product_id')->unique()->count(),
            'unique_users' => $events->pluck('user_id')->filter()->unique()->count(),
            'average_per_day' => round($events->count() / max($days, 1), 2),
        ];
    }

    /**
     * Get dashboard data.
     *
     * @return array<string, array<string, int|array<string, int>|array<int, array<string, string|int|null>>>|int>
     */
    public function getDashboardData(int $days = 30): array
    {
        $totalEvents = AnalyticsEvent::recent($days)->count();

        return [
            'overview' => [
                'total_events' => $totalEvents,
                'total_users' => AnalyticsEvent::recent($days)->distinct('user_id')->count('user_id'),
                'total_products' => AnalyticsEvent::recent($days)->distinct('product_id')->count('product_id'),
            ],
            'price_comparisons' => $this->getPriceComparisonStats($days),
            'most_viewed_products' => $this->getMostViewedProducts(10, $days),
            'most_searched_queries' => $this->getMostSearchedQueries(10, $days),
            'most_popular_stores' => $this->getMostPopularStores(10, $days),
        ];
    }

    /**
     * Get daily event counts.
     *
     * @return array<string, int>
     */
    public function getDailyEventCounts(string $eventType, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        return AnalyticsEvent::ofType($eventType)
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Clean old analytics data.
     */
    public function cleanOldData(int $daysToKeep = 365): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $count = AnalyticsEvent::where('created_at', '<', $cutoffDate)->delete();

        Log::info('Cleaned old analytics data', [
            'days_to_keep' => $daysToKeep,
            'records_deleted' => $count,
        ]);

        if (! is_int($count)) {
            return 0;
        }

        return $count;
    }
}
