<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Backup\BackupFileService;
use App\Services\Backup\BackupListService;
use App\Services\Backup\BackupService;
use App\Services\Backup\BackupValidator;
use App\Services\Backup\RestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    private BackupService $backupService;

    private BackupValidator $backupValidator;

    private BackupListService $backupListService;

    private BackupFileService $backupFileService;

    private RestoreService $restoreService;

    private string $backupPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');
        $this->backupService = new BackupService($this->backupPath);
        $this->backupValidator = new BackupValidator;
        $this->backupListService = new BackupListService($this->backupPath);
        $this->backupFileService = new BackupFileService($this->backupPath);
        $this->restoreService = new RestoreService($this->backupPath);
    }

    /**
     * Get all backups.
     */
    public function index(): JsonResponse
    {
        try {
            $backups = $this->backupListService->getBackupsList();

            return $this->createSuccessResponse($backups, 'Backups retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleError($e, 'getting backups');
        }
    }

    /**
     * Create a new backup.
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $backupConfig = $this->backupValidator->validateBackupRequest($request);
            $backup = $this->backupService->createBackup($backupConfig);

            return $this->createSuccessResponse($backup, 'Backup created successfully');
        } catch (\Exception $e) {
            return $this->handleError($e, 'creating backup');
        }
    }

    /**
     * Download a backup.
     */
    public function download(string $id): JsonResponse
    {
        try {
            $backup = $this->backupListService->getBackupById($id);

            if (! $backup) {
                return $this->createNotFoundResponse('Backup not found');
            }

            if (! $this->backupFileService->backupFileExists($backup)) {
                return $this->createNotFoundResponse('Backup file not found');
            }

            return $this->createDownloadResponse($backup);
        } catch (\Exception $e) {
            return $this->handleError($e, 'downloading backup');
        }
    }

    /**
     * Delete a backup.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $backup = $this->backupListService->getBackupById($id);

            if (! $backup) {
                return $this->createNotFoundResponse('Backup not found');
            }

            $this->backupFileService->deleteBackupFile($backup, $id);

            return $this->createSuccessResponse([], 'Backup deleted successfully');
        } catch (\Exception $e) {
            return $this->handleError($e, 'deleting backup');
        }
    }

    /**
     * Restore from backup.
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $backup = $this->backupListService->getBackupById($id);

            if (! $backup) {
                return $this->createNotFoundResponse('Backup not found');
            }

            $result = $this->restoreService->restoreFromBackup($backup);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'restoring backup');
        }
    }

    /**
     * Get backup statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->backupListService->calculateBackupStatistics();

            return $this->createSuccessResponse($stats, 'Backup statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleError($e, 'getting backup statistics');
        }
    }

    /**
     * Create success response.
     *
     * @param  array<string, mixed>  $data
     */
    private function createSuccessResponse(array $data, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ]);
    }

    /**
     * Create not found response.
     */
    private function createNotFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Create download response for backup.
     *
     * @param  array<string, string|int|null>  $backup
     */
    private function createDownloadResponse(array $backup): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Backup ready for download',
            'data' => [
                'filename' => $backup['filename'],
                'download_url' => $this->buildDownloadUrl($backup),
                'size' => $this->backupFileService->getBackupFileSize($backup),
                'expires_at' => now()->addHours(24)->toISOString(),
            ],
        ]);
    }

    /**
     * Build download URL for backup.
     *
     * @param  array<string, string|int|null>  $backup
     */
    private function buildDownloadUrl(array $backup): string
    {
        $filename = is_string($backup['filename'] ?? '') ? (string) ($backup['filename'] ?? '') : '';

        return url('storage/backups/'.$filename);
    }

    /**
     * Handle errors and return error response.
     */
    private function handleError(\Exception $e, string $operation): JsonResponse
    {
        Log::error("Error {$operation}: ".$e->getMessage());

        return response()->json([
            'success' => false,
            'message' => "Failed to {$operation}",
            'error' => $e->getMessage(),
        ], 500);
    }
}
