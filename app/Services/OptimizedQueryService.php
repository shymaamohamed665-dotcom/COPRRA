<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class OptimizedQueryService
{
    /**
     * @param  array<string, string|int|bool|array<string, string>>  $filters
     */
    public function getProductsWithDetails(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->buildProductQuery();
        $this->applyProductFilters($query, $filters);
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function getProductDetails(int $productId): ?Product
    {
        return Product::with([
            'category:id,name',
            'brand:id,name',
            'reviews' => $this->getReviewsQuery(),
            'reviews.user:id,name',
            'priceOffers' => $this->getPriceOffersQuery(),
        ])->find($productId);
    }

    /**
     * @param  array<string, string|int>  $filters
     */
    public function getUserOrders(int $userId, array $filters = []): Collection
    {
        $query = $this->buildUserOrdersQuery($userId);
        $this->applyOrderFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * @return array<string, int|float>
     */
    public function getDashboardAnalytics(): array
    {
        $analyticsResult = DB::select('
            SELECT
                (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users,
                (SELECT COUNT(*) FROM products WHERE is_active = 1) as total_products,
                (SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_orders,
                (
                    SELECT COALESCE(SUM(total_amount), 0)
                    FROM orders
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ) as monthly_revenue
            LIMIT 1
        ');

        return $this->formatAnalyticsResult($analyticsResult[0] ?? null);
    }

    private function buildProductQuery(): Builder
    {
        return Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'reviews' => static fn (Relation $q) => $q->select(['id', 'product_id', 'rating']),
            ])
            ->select([
                'id',
                'name',
                'price',
                'category_id',
                'brand_id',
                'is_active',
                'created_at',
                'description',
            ])
            ->where('is_active', true);
    }

    /**
     * @param  array<string, string|int|float>  $filters
     */
    private function applyProductFilters(Builder $query, array $filters): void
    {
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['brand_id']) && $filters['brand_id']) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (isset($filters['search']) && is_string($filters['search']) && $filters['search'] !== '') {
            $query->where('name', 'LIKE', '%'.$filters['search'].'%');
        }
    }

    private function getReviewsQuery(): Closure
    {
        return static function (Builder $q): void {
            $q->with('user:id,name')
                ->where('is_approved', true)
                ->orderBy('created_at', 'desc')
                ->limit(10);
        };
    }

    private function getPriceOffersQuery(): Closure
    {
        return static function (Builder $q): void {
            $q->where('is_available', true)
                ->orderBy('price', 'asc');
        };
    }

    private function buildUserOrdersQuery(int $userId): Builder
    {
        return Order::with([
            'items' => static function (Builder $q): void {
                $q->with('product:id,name')
                    ->select('id', 'order_id', 'product_id', 'quantity', 'price');
            },
        ])
            ->select([
                'id',
                'user_id',
                'status',
                'subtotal',
                'tax_amount',
                'shipping_amount',
                'total_amount',
                'created_at',
            ])
            ->where('user_id', $userId);
    }

    /**
     * @param  array<string, string|int>  $filters
     */
    private function applyOrderFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * @return array<string, int|float>
     */
    private function formatAnalyticsResult(?object $analytics): array
    {
        $defaults = [
            'total_users' => 0,
            'total_products' => 0,
            'recent_orders' => 0,
            'monthly_revenue' => 0.0,
        ];

        if (! is_object($analytics)) {
            return $defaults;
        }

        return [
            'total_users' => (int) ($analytics->total_users ?? 0),
            'total_products' => (int) ($analytics->total_products ?? 0),
            'recent_orders' => (int) ($analytics->recent_orders ?? 0),
            'monthly_revenue' => (float) ($analytics->monthly_revenue ?? 0.0),
        ];
    }
}
