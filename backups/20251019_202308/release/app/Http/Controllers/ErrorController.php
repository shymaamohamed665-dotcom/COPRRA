<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LogProcessing\LogProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class ErrorController extends Controller
{
    public function __construct(
        private readonly LogProcessingService $logProcessingService
    ) {}

    /**
     * Display error dashboard.
     */
    public function index(Request $request): View|JsonResponse
    {
        $logFiles = $this->logProcessingService->getLogFiles();

        $errors = $this->logProcessingService->processLogFilesForRecentErrors($logFiles);
        $errorStats = $this->logProcessingService->processErrorStatistics($logFiles);
        $systemHealth = $this->logProcessingService->getSystemHealth();

        if ($request->expectsJson()) {
            return $this->handleIndexJsonResponse($errors, $errorStats, $systemHealth);
        }

        return view('admin.errors.index', [
            'errors' => $errors,
            'statistics' => $errorStats,
            'systemHealth' => $systemHealth,
        ]);
    }

    /**
     * Display error details.
     */
    public function show(Request $request, string $id): View|JsonResponse
    {
        $logFiles = $this->logProcessingService->getLogFiles();
        $error = $this->logProcessingService->getErrorById($logFiles, $id);

        if (! $error) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error not found',
                ], 404);
            }

            return view('errors.404');
        }

        if ($request->expectsJson()) {
            return $this->handleShowJsonResponse($error);
        }

        /** @var view-string $view */
        $view = 'errors.details';

        return view($view, ['error' => $error]);
    }

    /**
     * Get recent errors.
     *
     * @return list<array{
     *     id: string,
     *     timestamp: string,
     *     level: string,
     *     type: string,
     *     message: string,
     *     context: array<string, string>
     * }>
     */
    public function getRecentErrors(int $limit = 50): array
    {
        try {
            $logFiles = glob(storage_path('logs/*.log'));
            $errors = $this->processLogFilesForRecentErrors($logFiles ? $logFiles : []);

            // Sort by timestamp (newest first)
            usort($errors, static function (array $a, array $b): int {
                $timestampA = isset($a['timestamp']) && is_string($a['timestamp'])
                    ? strtotime($a['timestamp'])
                    : 0;
                $timestampB = isset($b['timestamp']) && is_string($b['timestamp'])
                    ? strtotime($b['timestamp'])
                    : 0;

                return (int) $timestampB - (int) $timestampA;
            });

            return array_slice($errors, 0, $limit);
        } catch (\Exception $e) {
            Log::error('Failed to get recent errors', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get error statistics.
     *
     * @return array{
     *     total_errors: int,
     *     critical_errors: int,
     *     errors_by_type: array<string, int>,
     *     errors_by_hour: array<string, int>,
     *     errors_by_day: array<string, int>
     * }
     */
    public function getErrorStatistics(): array
    {
        try {
            $logFiles = glob(storage_path('logs/*.log'));

            return $this->processErrorStatistics($logFiles ? $logFiles : []);
        } catch (\Exception $e) {
            Log::error('Failed to get error statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'total_errors' => 0,
                'critical_errors' => 0,
                'errors_by_type' => [],
                'errors_by_hour' => [],
                'errors_by_day' => [],
            ];
        }
    }

    /**
     * Get system health.
     *
     * @return array{
     *     database: array{status: string, message: string},
     *     cache: array{status: string, message: string},
     *     storage: array{status: string, message: string},
     *     memory: array{status: string, message: string},
     *     disk_space: array{status: string, message: string},
     *     overall: string
     * }
     */
    public function getSystemHealth(): array
    {
        try {
            $health = [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'storage' => $this->checkStorageHealth(),
                'memory' => $this->checkMemoryHealth(),
                'disk_space' => $this->checkDiskSpaceHealth(),
            ];

            $health['overall'] = $this->calculateOverallHealth($health);

            return $health;
        } catch (\Exception $e) {
            Log::error('Failed to get system health', [
                'error' => $e->getMessage(),
            ]);

            return [
                'overall' => 'unknown',
                'database' => ['status' => 'unknown'],
                'cache' => ['status' => 'unknown'],
                'storage' => ['status' => 'unknown'],
                'memory' => ['status' => 'unknown'],
                'disk_space' => ['status' => 'unknown'],
            ];
        }
    }

    /**
     * Handle JSON response for the index method.
     *
     * @param  list<array{
     *     id: string,
     *     timestamp: string,
     *     level: string,
     *     type: string,
     *     message: string,
     *     context: array<string, string>
     * }>  $errors
     * @param  array{
     *     total_errors: int,
     *     critical_errors: int,
     *     errors_by_type: array<string, int>,
     *     errors_by_hour: array<string, int>,
     *     errors_by_day: array<string, int>
     * }  $errorStats
     * @param  array{
     *     overall: string,
     *     database: array{status: string},
     *     cache: array{status: string},
     *     storage: array{status: string},
     *     memory: array{status: string},
     *     disk_space: array{status: string}
     * }  $systemHealth
     */
    private function handleIndexJsonResponse(array $errors, array $errorStats, array $systemHealth): JsonResponse
    {
        $overallHealth = $this->logProcessingService->calculateOverallHealth($systemHealth);

        return response()->json([
            'success' => true,
            'data' => [
                'errors' => $errors,
                'statistics' => $errorStats,
                'system_health' => $systemHealth,
                'overall_health' => $overallHealth,
            ],
        ]);
    }

    /**
     * Handle JSON response for the show method.
     *
     * @param  array{
     *     id: string,
     *     timestamp: string,
     *     level: string,
     *     type: string,
     *     message: string,
     *     context: array<string, string>
     * }  $error
     */
    private function handleShowJsonResponse(array $error): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $error,
        ]);
    }
}
