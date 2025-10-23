<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $config = Config::get('monitoring.health_checks', [
            'enabled' => true,
            'checks' => [
                'database' => true,
                'cache' => true,
                'storage' => true,
                'queue' => true,
            ],
        ]);

        if (! ($config['enabled'] ?? true)) {
            return response()->json([
                'status' => 'disabled',
                'timestamp' => now(),
            ]);
        }

        $checks = $config['checks'] ?? [];

        $dbStatus = $checks['database'] ?? true ? $this->checkDatabase() : 'skipped';
        $cacheStatus = $checks['cache'] ?? true ? $this->checkCache() : 'skipped';
        $storageStatus = $checks['storage'] ?? true ? $this->checkStorage() : 'skipped';
        $queueStatus = $checks['queue'] ?? true ? $this->checkQueue() : 'skipped';

        $overall = $this->calculateOverallStatus([$dbStatus, $cacheStatus, $storageStatus, $queueStatus]);
        $statusCode = $overall === 'healthy' ? 200 : 503;

        return response()->json([
            'status' => $overall,
            'timestamp' => now(),
            'services' => [
                'database' => $dbStatus,
                'cache' => $cacheStatus,
                'storage' => $storageStatus,
                'queue' => $queueStatus,
            ],
        ], $statusCode);
    }

    private function calculateOverallStatus(array $statuses): string
    {
        foreach ($statuses as $status) {
            if ($status === 'down') {
                return 'degraded';
            }
        }

        return 'healthy';
    }

    private function checkDatabase(): string
    {
        try {
            // Simple query to verify connectivity
            DB::select('SELECT 1');

            return 'up';
        } catch (\Throwable $e) {
            return 'down';
        }
    }

    private function checkCache(): string
    {
        try {
            $storeName = Config::get('cache.default', 'redis');
            $cacheStore = Cache::store($storeName);
            $cacheStore->put('health_check', 'ok', 10);

            return $cacheStore->get('health_check') === 'ok' ? 'up' : 'down';
        } catch (\Throwable $e) {
            return 'down';
        }
    }

    private function checkStorage(): string
    {
        try {
            $dir = storage_path('app/health');
            if (! is_dir($dir)) {
                mkdir($dir, 0o755, true);
            }
            // Ensure writability using Storage facade
            $ok = Storage::disk('local')->put('health/probe.txt', 'ok');
            Storage::disk('local')->delete('health/probe.txt');

            return $ok ? 'up' : 'down';
        } catch (\Throwable $e) {
            return 'down';
        }
    }

    private function checkQueue(): string
    {
        try {
            $driver = Config::get('queue.default', 'sync');
            if ($driver === 'redis') {
                return Redis::connection()->ping() === 'PONG' ? 'up' : 'down';
            }
            // For other drivers, attempt to resolve default connection
            $defaultDriver = Queue::getDefaultDriver();

            return $defaultDriver !== '' ? 'up' : 'down';
        } catch (\Throwable $e) {
            return 'down';
        }
    }
}
