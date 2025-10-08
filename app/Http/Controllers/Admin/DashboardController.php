<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\FileSecurityService;
use App\Services\LoginAttemptService;
use App\Services\UserBanService;
use Illuminate\Contracts\Cache\Factory as Cache;
use Illuminate\Database\DatabaseManager as DB;
use Illuminate\Filesystem\FilesystemManager as Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    private LoginAttemptService $loginAttemptService;

    private UserBanService $userBanService;

    private FileSecurityService $fileSecurityService;

    private User $user;

    private Product $product;

    private Cache $cache;

    private DB $db;

    private Storage $storage;

    public function __construct(
        LoginAttemptService $loginAttemptService,
        UserBanService $userBanService,
        FileSecurityService $fileSecurityService,
        User $user,
        Product $product,
        Cache $cache,
        DB $db,
        Storage $storage
    ) {
        $this->loginAttemptService = $loginAttemptService;
        $this->userBanService = $userBanService;
        $this->fileSecurityService = $fileSecurityService;
        $this->user = $user;
        $this->product = $product;
        $this->cache = $cache;
        $this->db = $db;
        $this->storage = $storage;

        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index(): View
    {
        $statistics = $this->getDashboardStatistics();

        return view('admin.dashboard', ['statistics' => $statistics]);
    }

    public function getStatistics(): JsonResponse
    {
        $statistics = $this->getDashboardStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    public function getRealTimeStats(): JsonResponse
    {
        $stats = [
            'online_users' => $this->getOnlineUsersCount(),
            'recent_activities' => $this->getRecentActivities(),
            'system_health' => $this->getSystemHealth(),
            'security_alerts' => $this->getSecurityAlerts(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * @return array<string, array<string, array<string, int|string>|int|string>|array<string, int|array<string, int>>|array<string, int>>
     */
    private function getDashboardStatistics(): array
    {
        return $this->compileDashboardStatistics();
    }

    /**
     * @return array<string, array<string, int>|int>
     */
    private function compileDashboardStatistics(): array
    {
        return [
            'users' => $this->getUserStatistics(),
            'products' => $this->getProductStatistics(),
            'orders' => $this->getOrderStatistics(),
            'revenue' => $this->getRevenueStatistics(),
            'security' => $this->getSecurityStatistics(),
            'system' => $this->getSystemStatistics(),
            'recent_activities' => $this->getRecentActivities(),
            'charts' => $this->getChartData(),
        ];
    }

    /**
     * Get count of users based on a condition.
     *
     * @param  \Closure  $condition
     * @return int
     */
    private function getUserCount(\Closure $condition): int
    {
        return $condition($this->user)->count();
    }

    /**
     * @return array<string, int|array<string, int>>
     */
    private function getUserStatistics(): array
    {
        return [
            'total_users' => $this->getUserCount(fn ($query) => $query),
            'active_users' => $this->getUserCount(fn ($query) => $query->where('is_active', true)),
            'blocked_users' => 0, // Placeholder - no blocked users column
            'verified_users' => $this->getUserCount(fn ($query) => $query->whereNotNull('email_verified_at')),
            'new_users_today' => $this->getUserCount(fn ($query) => $query->whereDate('created_at', today())),
            'new_users_this_week' => $this->getUserCount(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            'new_users_this_month' => $this->getUserCount(fn ($query) => $query->whereMonth('created_at', now()->month)),
        ];
    }

    /**
     * Get count of products based on a condition.
     *
     * @param  \Closure  $condition
     * @return int
     */
    private function getProductCount(\Closure $condition): int
    {
        return $condition($this->product)->count();
    }

    /**
     * @return array<string, int>
     */
    private function getProductStatistics(): array
    {
        return [
            'total_products' => $this->getProductCount(fn ($query) => $query),
            'active_products' => $this->getProductCount(fn ($query) => $query->where('is_active', true)),
            'featured_products' => $this->getProductCount(fn ($query) => $query->where('is_featured', true)),
            'out_of_stock' => $this->getProductCount(fn ($query) => $query->where('stock_quantity', 0)),
            'low_stock' => $this->getProductCount(fn ($query) => $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10)),
            'new_products_today' => $this->getProductCount(fn ($query) => $query->whereDate('created_at', today())),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getOrderStatistics(): array
    {
        return [
            'total_orders' => 0, // Placeholder
            'pending_orders' => 0, // Placeholder
            'completed_orders' => 0, // Placeholder
            'cancelled_orders' => 0, // Placeholder
            'orders_today' => 0, // Placeholder
            'orders_this_week' => 0, // Placeholder
            'orders_this_month' => 0, // Placeholder
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getRevenueStatistics(): array
    {
        return [
            'total_revenue' => 0, // Placeholder
            'revenue_today' => 0, // Placeholder
            'revenue_this_week' => 0, // Placeholder
            'revenue_this_month' => 0, // Placeholder
            'average_order_value' => 0, // Placeholder
            'revenue_growth' => 0, // Placeholder
        ];
    }

    /**
     * @return array<string, array<string, int|string>|int|string>
     */
    private function getSecurityStatistics(): array
    {
        return [
            'login_attempts' => $this->loginAttemptService->getStatistics(),
            'banned_users' => $this->userBanService->getBanStatistics(),
            'file_security' => $this->fileSecurityService->getStatistics(),
            'failed_logins_today' => $this->getFailedLoginsToday(),
            'security_incidents' => $this->getSecurityIncidents(),
        ];
    }

    /**
     * @return array<string, array<string, int|string>|int|string>
     */
    private function getSystemStatistics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'disk_usage' => $this->getDiskUsage(),
            'database_size' => $this->getDatabaseSize(),
            'cache_status' => $this->getCacheStatus(),
        ];
    }

    /**
     * @return array<string, array<string, int|string>|int|string>
     */
    private function getRecentActivities(): array
    {
        // Get recent activities from AuditService
        return [];
    }

    /**
     * @return array<string, array<int, array<string, int|string>>|array<empty, empty>>
     */
    private function getChartData(): array
    {
        return [
            'user_registrations' => $this->getUserRegistrationChart(),
            'product_views' => $this->getProductViewsChart(),
            'revenue_chart' => $this->getRevenueChart(),
            'security_incidents' => $this->getSecurityIncidentsChart(),
        ];
    }

    private function getOnlineUsersCount(): int
    {
        // This would require a proper online users tracking system
        return 0;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'memory' => $this->checkMemoryHealth(),
        ];
    }

    /**
     * @return array<string, int|array<empty, empty>>
     */
    private function getSecurityAlerts(): array
    {
        return [
            'failed_logins' => $this->getFailedLoginsToday(),
            'blocked_ips' => count($this->loginAttemptService->getBlockedIps()),
            'banned_users' => count($this->userBanService->getBannedUsers()),
            'security_incidents' => $this->getSecurityIncidents(),
        ];
    }

    private function getFailedLoginsToday(): int
    {
        // This would require proper logging implementation
        return 0;
    }

    /**
     * @return array<empty, empty>
     */
    private function getSecurityIncidents(): array
    {
        // This would require proper security incident tracking
        return [];
    }

    /**
     * @return array<string, float|int>
     */
    private function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');

        if ($total === false || $free === false) {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'percentage' => 0.0,
            ];
        }

        $used = $total - $free;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage' => round($used / $total * 100, 2),
        ];
    }

    private function getDatabaseSize(): int
    {
        // This would require database size calculation
        return 0;
    }

    /**
     * @return array<string, string>
     */
    private function getCacheStatus(): array
    {
        try {
            $this->cache->put('test_key', 'test_value', 1);
            $status = $this->cache->get('test_key') === 'test_value';
            $this->cache->forget('test_key');

            return [
                'status' => $status ? 'working' : 'error',
                'driver' => is_string(config('cache.default')) ? config('cache.default') : 'unknown',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'driver' => is_string(config('cache.default')) ? config('cache.default') : 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform a health check and return standardized result.
     *
     * @param  \Closure  $checkFunction
     * @param  string  $successMessage
     * @return array<string, string>
     */
    private function performHealthCheck(\Closure $checkFunction, string $successMessage): array
    {
        try {
            $result = $checkFunction();
            return [
                'status' => $result ? 'healthy' : 'error',
                'message' => $successMessage
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, string>
     */
    private function checkDatabaseHealth(): array
    {
        return $this->performHealthCheck(
            function () {
                $this->db->connection()->getPdo();
                return true;
            },
            'Database connection successful'
        );
    }

    /**
     * @return array<string, string>
     */
    private function checkCacheHealth(): array
    {
        return $this->performHealthCheck(
            function () {
                $this->cache->put('health_check', 'ok', 1);
                $result = $this->cache->get('health_check');
                $this->cache->forget('health_check');
                return $result === 'ok';
            },
            'Cache test completed'
        );
    }

    /**
     * @return array<string, string>
     */
    private function checkStorageHealth(): array
    {
        return $this->performHealthCheck(
            function () {
                $testFile = 'health_check_' . time() . '.txt';
                $this->storage->put($testFile, 'test');
                $result = $this->storage->get($testFile);
                $this->storage->delete($testFile);
                return is_string($result) && $result === 'test';
            },
            'Storage test completed'
         );
     }

    /**
     * @return array<string, float|int|string>
     */
    private function checkMemoryHealth(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitString = is_string($memoryLimit) ? $memoryLimit : '128M';
        $memoryLimitBytes = $this->convertToBytes($memoryLimitString);
        $percentage = $memoryUsage / $memoryLimitBytes * 100;

        return [
            'status' => $percentage > 90 ? 'warning' : 'healthy',
            'usage' => $memoryUsage,
            'limit' => $memoryLimitBytes,
            'percentage' => round($percentage, 2),
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function getUserRegistrationChart(): array
    {
        // This would require chart data generation
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $this->user->whereDate('created_at', $date)->count(),
            ];
        }

        return $data;
    }

    /**
     * @return array<empty, empty>
     */
    private function getProductViewsChart(): array
    {
        return [];
    }

    /**
     * @return array<empty, empty>
     */
    private function getRevenueChart(): array
    {
        return [];
    }

    /**
     * @return array<empty, empty>
     */
    private function getSecurityIncidentsChart(): array
    {
        return [];
    }

    private function convertToBytes(string $from): int
    {
        $number = (int) substr($from, 0, -1);
        $suffix = strtoupper(substr($from, -1));

        return match ($suffix) {
            'K' => $number * 1024,
            'M' => $number * 1024 * 1024,
            'G' => $number * 1024 * 1024 * 1024,
            default => $number,
        };
    }
}