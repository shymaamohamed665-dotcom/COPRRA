<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 */
class DocumentationController extends BaseApiController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'COPRRA API is running',
            'version' => '1.0.0',
            'timestamp' => Date::now()->toISOString(),
        ]);
    }

    public function health(): JsonResponse
    {
        $timestamp = Date::now()->toISOString();
        $version = '1.0.0';

        // Environment
        $environment = (string) config('app.env');

        // Database health
        $database = 'connected';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $database = 'disconnected';
        }

        // Cache health
        $cache = 'working';

        try {
            Cache::put('health_check', 'ok', 60);
            $cacheValue = Cache::get('health_check');
            if ($cacheValue !== 'ok') {
                $cache = 'not_working';
            }
        } catch (\Throwable $e) {
            $cache = 'not_working';
        }

        // Storage health
        $storage = 'writable';

        try {
            Storage::disk('local')->put('health_check.txt', 'ok');
        } catch (\Throwable $e) {
            $storage = 'not_writable';
        }

        $isHealthy = $database === 'connected' && $cache === 'working' && $storage === 'writable';
        $statusCode = $isHealthy ? 200 : 503;

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => $timestamp,
            'version' => $version,
            'environment' => $environment,
            'database' => $database,
            'cache' => $cache,
            'storage' => $storage,
        ], $statusCode);
    }
}
