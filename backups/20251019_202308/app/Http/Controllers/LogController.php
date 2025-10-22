<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    protected string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    /**
     * Get application logs.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $logData = $this->getFilteredLogs($request);

            if (! $logData['success']) {
                return response()->json($logData, $logData['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'data' => $logData['data'],
                'message' => 'Logs retrieved successfully',
            ]);
        }, 'retrieving logs');
    }

    /**
     * Clear application logs.
     */
    public function clear(): JsonResponse
    {
        return $this->executeWithErrorHandling(function () {
            File::put($this->logPath, '');
            Log::info('Log file cleared by user: '.(auth()->id() ?? 'Guest'));

            return response()->json([
                'success' => true,
                'message' => 'Log file cleared successfully',
            ]);
        }, 'clearing log file');
    }

    /**
     * Download log file.
     */
    public function download(): JsonResponse
    {
        return $this->executeWithErrorHandling(function () {
            if (! File::exists($this->logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log file not found',
                ], 404);
            }

            $filename = 'laravel_'.now()->format('Y-m-d_H-i-s').'.log';
            $downloadPath = storage_path('app/'.$filename);

            File::copy($this->logPath, $downloadPath);

            return response()->json([
                'success' => true,
                'message' => 'Log file prepared for download',
                'data' => [
                    'filename' => $filename,
                    'download_url' => url('storage/'.$filename),
                    'expires_at' => now()->addHours(24)->toISOString(),
                ],
            ]);
        }, 'preparing log download');
    }

    /**
     * Get log statistics.
     */
    public function getStatistics(): JsonResponse
    {
        return $this->executeWithErrorHandling(function () {
            if (! File::exists($this->logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log file not found',
                ], 404);
            }

            $stats = $this->calculateLogStatistics($this->logPath);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Log statistics retrieved successfully',
            ]);
        }, 'getting log statistics');
    }

    /**
     * Parse log file with filters.
     *
     * @param  array<string>  $allowedLevels
     * @return array<string, bool|string|array>
     */
    public function parseLogFile(array $allowedLevels = []): array
    {
        return $this->executeLogOperation(function () use ($allowedLevels): array {
            if (! File::exists($this->logPath)) {
                return [
                    'success' => false,
                    'message' => 'Log file not found',
                ];
            }

            $logs = File::get($this->logPath);
            if ($logs === null || $logs === '') {
                return [
                    'success' => false,
                    'message' => 'Failed to read log file',
                ];
            }

            $lines = explode("\n", $logs);
            $reversedLines = array_reverse($lines);

            return [
                'success' => true,
                'data' => $this->filterLinesByLevels($reversedLines, $allowedLevels),
                'message' => 'Log file parsed successfully',
            ];
        }, 'parsing log file');
    }

    /**
     * Parse access log file.
     *
     * @return array<string, bool|string|array>
     */
    public function parseAccessLogFile(): array
    {
        return $this->executeLogOperation(function (): array {
            $accessLogPath = storage_path('logs/access.log');

            if (! File::exists($accessLogPath)) {
                return [
                    'success' => false,
                    'message' => 'Access log file not found',
                ];
            }

            $logs = File::get($accessLogPath);
            if ($logs === null || $logs === '') {
                return [
                    'success' => false,
                    'message' => 'Failed to read access log file',
                ];
            }

            $lines = explode("\n", $logs);
            $reversedLines = array_reverse($lines);

            return [
                'success' => true,
                'data' => $reversedLines,
                'message' => 'Access log file parsed successfully',
            ];
        }, 'parsing access log file');
    }

    /**
     * Get recent errors.
     */
    public function getRecentErrors(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $limit = $request->get('limit', 50);
            $parsedLogs = $this->parseLogFile(['ERROR', 'CRITICAL', 'EMERGENCY']);

            if (! $parsedLogs['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $parsedLogs['message'],
                ], 500);
            }

            $data = $parsedLogs['data'] ?? [];
            $errors = is_array($data) ? array_slice($data, 0, is_numeric($limit) ? (int) $limit : 50) : [];

            return response()->json([
                'success' => true,
                'data' => $errors,
                'message' => 'Recent errors retrieved successfully',
            ]);
        }, 'getting recent errors');
    }

    /**
     * Get log levels.
     */
    public function getLogLevels(): JsonResponse
    {
        return $this->executeWithErrorHandling(function () {
            return response()->json([
                'success' => true,
                'data' => $this->getAvailableLogLevels(),
                'message' => 'Log levels retrieved successfully',
            ]);
        }, 'getting log levels');
    }

    /**
     * Export logs to file.
     *
     * @param  list<array<string, string|array>>  $logs
     */
    public function exportLogsToFile(array $logs): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($logs) {
            $filePath = $this->prepareExportFilePath();
            $this->writeLogsToCsv($filePath, $logs);

            return response()->json([
                'success' => true,
                'message' => 'Logs exported successfully',
                'data' => [
                    'filename' => basename($filePath),
                    'download_url' => url('storage/'.basename($filePath)),
                    'expires_at' => now()->addHours(24)->toISOString(),
                ],
            ]);
        }, 'exporting logs');
    }

    /**
     * Get audit logs for export.
     *
     * @return array<string, bool|string|array>
     */
    public function getAuditLogsForExport(): array
    {
        // Placeholder for audit logs
        return [
            'success' => true,
            'data' => [],
            'message' => 'Audit logs retrieved successfully',
        ];
    }

    /**
     * Get system logs for export.
     *
     * @return array<string, bool|string|array>
     */
    public function getSystemLogsForExport(): array
    {
        return $this->executeWithErrorHandling(function (): array {
            return $this->parseLogFile();
        }, 'getting system logs');
    }

    /**
     * Get access logs for export.
     *
     * @return array<string, bool|string|array>
     */
    public function getAccessLogsForExport(): array
    {
        return $this->executeWithErrorHandling(function (): array {
            return $this->parseAccessLogFile();
        }, 'getting access logs');
    }

    /**
     * Execute a callback with unified error handling.
     */
    private function executeWithErrorHandling(callable $callback, string $operation): JsonResponse
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            Log::error("Error {$operation}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => "Failed to {$operation}",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate log statistics.
     */
    private function calculateLogStatistics(string $logPath): array
    {
        $logs = File::get($logPath);
        $lines = explode("\n", $logs);

        return [
            'total_lines' => count($lines),
            'error_count' => $this->countLogLevel($lines, 'ERROR'),
            'warning_count' => $this->countLogLevel($lines, 'WARNING'),
            'info_count' => $this->countLogLevel($lines, 'INFO'),
            'debug_count' => $this->countLogLevel($lines, 'DEBUG'),
            'file_size' => File::size($logPath),
            'last_modified' => File::lastModified($logPath),
        ];
    }

    /**
     * Filter log lines by allowed levels.
     *
     * @param  array<string>  $lines
     * @param  array<string>  $allowedLevels
     * @return array<string>
     */
    private function filterLinesByLevels(array $lines, array $allowedLevels): array
    {
        if ($allowedLevels === []) {
            return $lines;
        }

        return array_filter($lines, static function (string $line) use ($allowedLevels): bool {
            foreach ($allowedLevels as $level) {
                if (str_contains($line, $level)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Execute log operation with error handling.
     *
     * @return array<string, bool|string|array>
     */
    private function executeLogOperation(callable $callback, string $operation): array
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            Log::error("Error {$operation}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => "Failed to {$operation}",
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available log levels.
     *
     * @return array<string, string>
     */
    private function getAvailableLogLevels(): array
    {
        return [
            'emergency' => 'Emergency',
            'alert' => 'Alert',
            'critical' => 'Critical',
            'error' => 'Error',
            'warning' => 'Warning',
            'notice' => 'Notice',
            'info' => 'Info',
            'debug' => 'Debug',
        ];
    }

    /**
     * Prepare export file path.
     */
    private function prepareExportFilePath(): string
    {
        $filename = 'logs_export_'.now()->format('Y-m-d_H-i-s').'.csv';

        return storage_path('app/'.$filename);
    }

    /**
     * Write logs to CSV file.
     *
     * @param  list<array<string, string|array>>  $logs
     */
    private function writeLogsToCsv(string $filePath, array $logs): void
    {
        $file = fopen($filePath, 'w');
        if ($file === false) {
            throw new \RuntimeException('Failed to create export file');
        }

        try {
            fputcsv($file, ['Timestamp', 'Level', 'Message']);
            fputcsv($file, array_values(array_map(static fn (array $log): string => is_string($log) ? $log : 'log', $logs)));
        } finally {
            fclose($file);
        }
    }

    /**
     * Retrieve and filter log entries based on request parameters.
     *
     * @return array<string, string|array|int|bool|null>
     */
    private function getFilteredLogs(Request $request): array
    {
        if (! File::exists($this->logPath)) {
            return [
                'success' => false,
                'message' => 'Log file not found',
                'status' => 404,
            ];
        }

        $logs = File::get($this->logPath);
        $lines = explode("\n", $logs);
        $reversedLines = array_reverse($lines);

        $reversedLines = $this->applyLevelFilter($reversedLines, $request);
        $reversedLines = $this->applyLimitFilter($reversedLines, $request);

        return [
            'success' => true,
            'data' => array_values($reversedLines),
        ];
    }

    /**
     * Apply level filter to log lines.
     *
     * @param  array<string>  $lines
     * @return array<string>
     */
    private function applyLevelFilter(array $lines, Request $request): array
    {
        if (! $request->has('level')) {
            return $lines;
        }

        $level = $request->get('level');
        if (! is_string($level)) {
            return $lines;
        }

        return array_filter($lines, static fn (string $line): bool => str_contains($line, $level));
    }

    /**
     * Apply limit filter to log lines.
     *
     * @param  array<string>  $lines
     * @return array<string>
     */
    private function applyLimitFilter(array $lines, Request $request): array
    {
        $limitInput = $request->get('limit', 100);
        $limit = is_numeric($limitInput) ? (int) $limitInput : 100;

        return array_slice($lines, 0, $limit);
    }

    /**
     * Count log level occurrences.
     *
     * @param  array<string>  $lines
     */
    private function countLogLevel(array $lines, string $level): int
    {
        $count = 0;
        foreach ($lines as $line) {
            if (str_contains($line, $level)) {
                $count++;
            }
        }

        return $count;
    }
}
