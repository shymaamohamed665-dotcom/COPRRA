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
            $event = AnalyticsEvent::create([
                'event_type' => $eventType,
                'event_name' => $eventName,
                'user_id' => $userId,
                'product_id' => $productId,
                'category_id' => $categoryId,
                'store_id' => $storeId,
                'metadata' => $metadata,
            ]);

            return $event;
        } catch (\Throwable $e) {
            Log::warning('Failed to track analytics event', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Track price comparison event.
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
    public function trackProductView(int $productId, ?int $userId = null, array $metadata = []): ?AnalyticsEvent
    {
        return $this->track(
            AnalyticsEvent::TYPE_PRODUCT_VIEW,
            'Product Viewed',
            $userId,
            $productId,
            null,
            null,
            $metadata
        );
    }

    /**
     * Track search event.
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
            [
                'query' => $query,
                'filters' => $filters,
            ]
        );
    }

    /**
     * Track store click event.
     */
    public function trackStoreClick(int $storeId, ?int $productId = null, array $metadata = []): ?AnalyticsEvent
    {
        return $this->track(
            AnalyticsEvent::TYPE_STORE_CLICK,
            'Store Clicked',
            null,
            $productId,
            null,
            $storeId,
            $metadata
        );
    }

    /**
     * Dashboard aggregates and top lists.
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

        return is_int($count) ? $count : 0;
    }

    /**
     * Price comparison statistics summary.
     */
    public function getPriceComparisonStats(int $days): array
    {
        $query = AnalyticsEvent::recent($days)->ofType(AnalyticsEvent::TYPE_PRICE_COMPARISON);

        $total = (int) $query->count();
        $uniqueProducts = (int) $query->whereNotNull('product_id')->distinct('product_id')->count('product_id');
        $uniqueUsers = (int) $query->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        return [
            'total_comparisons' => $total,
            'unique_products' => $uniqueProducts,
            'unique_users' => $uniqueUsers,
            'average_per_day' => $days > 0 ? ($total / $days) : 0.0,
        ];
    }

    public function getMostViewedProducts(int $limit, int $days): array
    {
        return AnalyticsEvent::recent($days)
            ->ofType(AnalyticsEvent::TYPE_PRODUCT_VIEW)
            ->select('product_id', DB::raw('COUNT(*) as views'))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['product_id' => $row->product_id, 'view_count' => (int) $row->views])
            ->toArray();
    }

    /**
     * Most searched queries: returns associative array [query => count].
     */
    public function getMostSearchedQueries(int $limit, int $days): array
    {
        // Use application-level aggregation to ensure portability across DB drivers
        $events = AnalyticsEvent::recent($days)
            ->ofType(AnalyticsEvent::TYPE_SEARCH)
            ->get(['metadata']);

        $counts = [];
        foreach ($events as $event) {
            $query = null;
            $meta = $event->metadata;
            if (is_array($meta) && array_key_exists('query', $meta)) {
                $query = (string) $meta['query'];
            }

            if ($query !== null && $query !== '') {
                $counts[$query] = ($counts[$query] ?? 0) + 1;
            }
        }

        // Sort by count desc, then by query asc for stability
        uasort($counts, function ($a, $b) {
            if ($a === $b) {
                return 0;
            }

            return ($a > $b) ? -1 : 1;
        });

        // Limit results
        $limited = array_slice($counts, 0, $limit, true);

        // Ensure integer values
        return array_map(fn ($c) => (int) $c, $limited);
    }

    /**
     * Most popular stores: returns array of ['store_id' => int, 'click_count' => int].
     */
    public function getMostPopularStores(int $limit, int $days): array
    {
        return AnalyticsEvent::recent($days)
            ->ofType(AnalyticsEvent::TYPE_STORE_CLICK)
            ->select('store_id', DB::raw('COUNT(*) as click_count'))
            ->whereNotNull('store_id')
            ->groupBy('store_id')
            ->orderByDesc('click_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['store_id' => $row->store_id, 'click_count' => (int) $row->click_count])
            ->toArray();
    }
}
