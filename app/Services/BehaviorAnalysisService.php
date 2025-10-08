<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class BehaviorAnalysisService
{
    /**
     * @param  array<string, string|int|float|bool|null>  $data
     */
    public function trackUserBehavior(User $user, string $action, array $data = []): void
    {
        DB::table('user_behaviors')->insert([
            'user_id' => $user->id,
            'action' => $action,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, array<string, int|string>|float|int>
     */
    public function getUserAnalytics(User $user): array
    {
        $cacheKey = "user_analytics_{$user->id}";

        return Cache::remember($cacheKey, 1800, function () use ($user): array {
            return [
                'purchase_history' => $this->getPurchaseHistory($user),
                'browsing_patterns' => $this->getBrowsingPatterns($user),
                'preferences' => $this->getUserPreferences($user),
                'engagement_score' => $this->calculateEngagementScore($user),
                'lifetime_value' => $this->calculateLifetimeValue($user),
                'recommendation_score' => $this->calculateRecommendationScore($user),
            ];
        });
    }

    /**
     * @return array<string, float|int|array<int, array<string, string|int|null>>>
     */
    public function getSiteAnalytics(): array
    {
        $cacheKey = 'site_analytics';

        return Cache::remember($cacheKey, 3600, function (): array {
            return [
                'total_users' => User::count(),
                'active_users' => $this->getActiveUsersCount(),
                'total_orders' => Order::count(),
                'total_revenue' => Order::sum('total_amount'),
                'average_order_value' => Order::avg('total_amount'),
                'conversion_rate' => $this->getConversionRate(),
                'most_viewed_products' => $this->getMostViewedProducts(),
                'top_selling_products' => $this->getTopSellingProducts(),
            ];
        });
    }

    /**
     * @return array<int, array<string, string|int|float|bool|array<int, array<string, string|int|float|bool|null>>|null>>
     */
    private function getPurchaseHistory(User $user): array
    {
        /** @var array<int, array<string, string|int|float|bool|array<int, array<string, string|int|float|bool|null>>|null>> $history */
        $history = Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(static function (Order $order): array {
                return [
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'products' => $order->items->map(static function (OrderItem $item): array {
                        $product = $item->product;

                        return [
                            'name' => $product ? $product->name : '',
                            'price' => $item->unit_price,
                            'quantity' => $item->quantity,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();

        return $history;
    }

    /**
     * @return array<string, int|array<int, int>>
     *
     * @psalm-suppress MissingReturnType, MissingReturnStatement
     */
    private function getBrowsingPatterns(User $user): array
    {
        $behaviors = DB::table('user_behaviors')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $patterns = [
            'page_views' => $behaviors->where('action', 'page_view')->count(),
            'product_views' => $behaviors->where('action', 'product_view')->count(),
            'search_queries' => $behaviors->where('action', 'search')->count(),
            'cart_additions' => $behaviors->where('action', 'cart_add')->count(),
            'wishlist_additions' => $behaviors->where('action', 'wishlist_add')->count(),
        ];

        $patterns['most_viewed_categories'] = $this->getMostViewedCategories($user);
        $patterns['peak_activity_hours'] = $this->getPeakActivityHours($user);

        return $patterns;
    }

    /**
     * @return array<string, array<int, int|float|null>|array<int, int>>
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

        $categories = $purchases->groupBy(static function (OrderItem $item): int {
            $product = $item->product;

            return $product && $product->category_id ? $product->category_id : 0;
        })
            ->map(static function ($items) {
                return $items->sum('quantity');
            })
            ->sortDesc()
            ->take(5);

        $brands = $purchases->groupBy(static function (OrderItem $item): int {
            $product = $item->product;

            return $product && $product->brand_id ? $product->brand_id : 0;
        })
            ->map(static function ($items) {
                return $items->sum('quantity');
            })
            ->sortDesc()
            ->take(5);

        $priceRange = $purchases->map(static function (OrderItem $item) {
            $product = $item->product;

            return $product ? $product->price : 0;
        });

        return [
            'preferred_categories' => $categories->keys()->toArray(),
            'preferred_brands' => $brands->keys()->toArray(),
            'price_range' => [
                'min' => $priceRange->min(),
                'max' => $priceRange->max(),
                'average' => $priceRange->avg(),
            ],
        ];
    }

    private function calculateEngagementScore(User $user): float
    {
        $behaviors = DB::table('user_behaviors')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $score = 0;

        // Page views (weight: 1)
        $score += $behaviors->where('action', 'page_view')->count() * 1;

        // Product views (weight: 2)
        $score += $behaviors->where('action', 'product_view')->count() * 2;

        // Search queries (weight: 3)
        $score += $behaviors->where('action', 'search')->count() * 3;

        // Cart additions (weight: 5)
        $score += $behaviors->where('action', 'cart_add')->count() * 5;

        // Purchases (weight: 10)
        $score += Order::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count() * 10;

        return min($score / 100, 1.0); // Normalize to 0-1
    }

    private function calculateLifetimeValue(User $user): float
    {
        $sum = Order::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        return is_numeric($sum) ? (float) $sum : 0.0;
    }

    private function calculateRecommendationScore(User $user): float
    {
        $engagementScore = $this->calculateEngagementScore($user);
        $lifetimeValue = $this->calculateLifetimeValue($user);
        $purchaseFrequency = $this->getPurchaseFrequency($user);

        return ($engagementScore * 0.4) + (min($lifetimeValue / 1000, 1) * 0.3) + (min($purchaseFrequency, 1) * 0.3);
    }

    private function getPurchaseFrequency(User $user): float
    {
        $firstPurchaseValue = Order::where('user_id', $user->id)->min('created_at');
        $firstPurchase = is_string($firstPurchaseValue) ? $firstPurchaseValue : null;

        if (! $firstPurchase) {
            return 0;
        }

        $daysSinceFirstPurchase = now()->diffInDays($firstPurchase);
        $totalPurchases = Order::where('user_id', $user->id)->count();

        return $daysSinceFirstPurchase > 0 ? $totalPurchases / $daysSinceFirstPurchase : 0;
    }

    private function getActiveUsersCount(): int
    {
        return User::whereHas('orders', static function ($query): void {
            $query->where('created_at', '>=', now()->subDays(30));
        })
            ->count();
    }

    private function getConversionRate(): float
    {
        $totalVisitors = DB::table('user_behaviors')
            ->where('action', 'page_view')
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct('user_id')
            ->count();

        $totalPurchases = Order::where('created_at', '>=', now()->subDays(30))->count();

        return $totalVisitors > 0 ? $totalPurchases / $totalVisitors * 100 : 0;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function getTopProducts(): array
    {
        /** @var \Illuminate\Database\Eloquent\Builder<Product> $query */
        $query = Product::select('products.*')->withCount([
            'orderItems as purchase_count' => static function (\Illuminate\Database\Eloquent\Builder $query): void {
                $query->whereHas('order', static function ($q): void {
                    $q->where('created_at', '>=', now()->subDays(30));
                });
            },
        ]);

        /** @var array<int, array<string, string|int|null>> $topProducts */
        $topProducts = $query->orderBy('purchase_count', 'desc')
            ->limit(10)
            ->get()
            ->map(static function (Product $product): array {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'purchase_count' => $product->purchase_count ?? 0,
                ];
            })
            ->toArray();

        return $topProducts;
    }

    /**
     * @return array<string, int>
     */
    private function getUserSegments(): array
    {
        return [
            'high_value' => User::whereHas('orders', static function ($query): void {
                $query->where('total_amount', '>', 500);
            })->count(),
            'frequent_buyers' => User::whereHas('orders', static function ($query): void {
                $query->where('created_at', '>=', now()->subDays(30));
            }, '>=', 3)->count(),
            'new_customers' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function getMostViewedCategories(User $user): array
    {
        /** @var \Illuminate\Database\Query\Builder $query */
        $query = DB::table('user_behaviors')
            ->where('user_id', $user->id)
            ->where('action', 'product_view')
            ->where('created_at', '>=', now()->subDays(30));

        /** @var array<int, int> $mostViewedCategories */
        $mostViewedCategories = $query->join('products', static function ($join): void {
            /** @var \Illuminate\Database\Query\JoinClause $join */
            $join->whereRaw("JSON_EXTRACT(user_behaviors.data, '$.product_id') = products.id");
        })
            ->select('products.category_id')
            ->selectRaw('COUNT(*) as view_count')
            ->groupBy('products.category_id')
            ->orderBy('view_count', 'desc')
            ->limit(5)
            ->pluck('category_id')
            ->toArray();

        return $mostViewedCategories;
    }

    /**
     * @return array<int, int>
     */
    private function getPeakActivityHours(User $user): array
    {
        return DB::table('user_behaviors')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('HOUR(created_at) as hour')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(3)
            ->pluck('hour')
            ->toArray();
    }
}
