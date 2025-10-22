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
    private readonly LoginAttemptService $loginAttemptService;

    private readonly UserBanService $userBanService;

    private readonly FileSecurityService $fileSecurityService;

    private readonly User $user;

    private readonly Product $product;

    private readonly Cache $cache;

    private readonly DB $db;

    private readonly Storage $storage;

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
     * @return array<array<array<array<int|string>|int|string>|int|string>>
     *
     * @psalm-return array{users: array<string, array<string, int>|int>, products: array<string, int>, orders: array<string, int>, revenue: array<string, int>, security: array<string, array<string, int|string>|int|string>, system: array<string, array<string, int|string>|int|string>, recent_activities: array<string, array<string, int|string>|int|string>, charts: array<string, array<int, array<string, int|string>>>}
     */
    private function getDashboardStatistics(): array
    {
        return $this->compileDashboardStatistics();
    }

    /**
     * @return array<array<array<array<int|string>|int|string>|int|string>>
     *
     * @psalm-return array{users: array<string, array<string, int>|int>, products: array<string, int>, orders: array<string, int>, revenue: array<string, int>, security: array<string, array<string, int|string>|int|string>, system: array<string, array<string, int|string>|int|string>, recent_activities: array<string, array<string, int|string>|int|string>, charts: array<string, array<int, array<string, int|string>>>}
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
     */
    private function getUserCount(\Closure $condition): int
    {
        return $condition($this->user)->count();
    }

    /**
     * @return array<int>
     *
     * @psalm-return array{total_users: int, active_users: int, blocked_users: 0, verified_users: int, new_users_today: int, new_users_this_week: int, new_users_this_month: int}
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
     */
    private function getProductCount(\Closure $condition): int
    {
        return $condition($this->product)->count();
    }

    /**
     * @return array<int>
     *
     * @psalm-return array{total_products: int, active_products: int, featured_products: int, out_of_stock: int, low_stock: int, new_products_today: int}
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
     * @return array<int>
     *
     * @psalm-return array{total_orders: 0, pending_orders: 0, completed_orders: 0, cancelled_orders: 0, orders_today: 0, orders_this_week: 0, orders_this_month: 0}
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
     * @return array<int>
     *
     * @psalm-return array{total_revenue: 0, revenue_today: 0, revenue_this_week: 0, revenue_this_month: 0, average_order_value: 0, revenue_growth: 0}
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
     * @return array<array<int|array<string>>|int>
     *
     * @psalm-return array{login_attempts: array{max_attempts: 5, lockout_duration: 15, blocked_emails_count: int<0, max>, blocked_ips_count: int<0, max>}, banned_users: array<string, array<string, string>|int>, file_security: array{allowed_extensions: list{'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'txt', 'rtf', 'xls', 'xlsx', 'csv', 'zip', 'rar', '7z'}, dangerous_extensions: list{'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh', 'ps1', 'psm1', 'psd1', 'ps1xml', 'psc1', 'psc2'}, max_file_size: 10485760, max_file_size_mb: 10}, failed_logins_today: int, security_incidents: array<never, never>}
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
     * @return array<array<float|int|string>|int|string>
     *
     * @psalm-return array{php_version: string, laravel_version: string, memory_usage: int, memory_peak: int, disk_usage: array{total: 0|float, used: 0|float, free: 0|float, percentage: float}, database_size: int, cache_status: array<string, string>}
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
     * @psalm-return array<never, never>
     */
    private function getRecentActivities(): array
    {
        // Get recent activities from AuditService
        return [];
    }

    /**
     * @return array<array<array<int|string>>>
     *
     * @psalm-return array{user_registrations: array<int, array<string, int|string>>, product_views: array<never, never>, revenue_chart: array<never, never>, security_incidents: array<never, never>}
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
     * @return array<array<float|int|string>>
     *
     * @psalm-return array{database: array<string, string>, cache: array<string, string>, storage: array<string, string>, memory: array{status: 'healthy'|'warning', usage: int, limit: int, percentage: float}}
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
     * @return array<array|int>
     *
     * @psalm-return array{failed_logins: int, blocked_ips: 0, banned_users: int<0, max>, security_incidents: array<never, never>}
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
     * @return array<float|int>
     *
     * @psalm-return array{total: 0|float, used: 0|float, free: 0|float, percentage: float}
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
     * @return array<string>
     *
     * @psalm-return array{status: 'error'|'healthy', message: string}
     */
    private function performHealthCheck(\Closure $checkFunction, string $successMessage): array
    {
        try {
            $result = $checkFunction();

            return [
                'status' => $result ? 'healthy' : 'error',
                'message' => $successMessage,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{status: 'error'|'healthy', message: string}
     */
    private function checkDatabaseHealth(): array
    {
        return $this->performHealthCheck(
            function (): true {
                $this->db->connection()->getPdo();

                return true;
            },
            'Database connection successful'
        );
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{status: 'error'|'healthy', message: string}
     */
    private function checkCacheHealth(): array
    {
        return $this->performHealthCheck(
            function (): bool {
                $this->cache->put('health_check', 'ok', 1);
                $result = $this->cache->get('health_check');
                $this->cache->forget('health_check');

                return $result === 'ok';
            },
            'Cache test completed'
        );
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{status: 'error'|'healthy', message: string}
     */
    private function checkStorageHealth(): array
    {
        return $this->performHealthCheck(
            function (): bool {
                $testFile = 'health_check_'.time().'.txt';
                $this->storage->put($testFile, 'test');
                $result = $this->storage->get($testFile);
                $this->storage->delete($testFile);

                return is_string($result) && $result === 'test';
            },
            'Storage test completed'
        );
    }

    /**
     * @return array<float|int|string>
     *
     * @psalm-return array{status: 'healthy'|'warning', usage: int, limit: int, percentage: float}
     */
    private function checkMemoryHealth(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitString = is_string($memoryLimit) ? $memoryLimit : '128M';
        $memoryLimitBytes = $this->convertToBytes($memoryLimitString);
        $percentage = $memoryUsage / $memoryLimitBytes * 100;
        $criticalThreshold = (float) config('coprra.storage.thresholds.critical', 90);

        return [
            'status' => $percentage > $criticalThreshold ? 'warning' : 'healthy',
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
        $bytesPerKb = (int) config('coprra.constants.bytes_per_kb', 1024);

        return match ($suffix) {
            'K' => $number * $bytesPerKb,
            'M' => $number * $bytesPerKb * $bytesPerKb,
            'G' => $number * $bytesPerKb * $bytesPerKb * $bytesPerKb,
            default => $number,
        };
    }
}
