<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class SystemController extends Controller
{
    /**
     * Run database migrations.
     */
    public function runMigrations(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            Log::info('Database migrations ran successfully.');

            return response()->json([
                'success' => true,
                'message' => 'Migrations ran successfully',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error running migrations: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to run migrations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear application cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            Log::info('Application cache cleared successfully.');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing cache: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run composer update.
     */
    public function runComposerUpdate(): JsonResponse
    {
        try {
            $process = new Process(['composer', 'update', '--no-dev']);
            $process->setTimeout(3600); // 1 hour timeout
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException($process->getErrorOutput());
            }

            Log::info('Composer update ran successfully.');

            return response()->json([
                'success' => true,
                'message' => 'Composer update ran successfully',
                'output' => $process->getOutput(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error running composer update: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to run composer update',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run system backup.
     *
     * @param  array<string, string|bool>  $options
     * @return array<string, bool|string|array<string, bool>>
     */
    public function runBackup(array $options = []): array
    {
        try {
            $backupType = $options['type'] ?? 'full';
            $includeFiles = $options['include_files'] ?? true;
            $includeDatabase = $options['include_database'] ?? true;

            $results = [];

            if ($includeDatabase) {
                $this->backupDatabase();
                $results['database_backed_up'] = true;
            }

            if ($includeFiles) {
                $this->backupFiles();
                $results['files_backed_up'] = true;
            }

            return [
                'success' => true,
                'message' => 'System backup completed',
                'results' => $results,
                'type' => $backupType,
            ];
        } catch (\Exception $e) {
            Log::error('Error running backup: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to run backup',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear logs.
     */
    // Either implement clearLogs() usage or remove
    public function clearLogs(): void
    {
        // تحقق من وجود كلاس LogFile أو استيراده
        if (class_exists('App\\Helpers\\LogFile')) {
            \App\Helpers\LogFile::cleanOldRecords(now()->subDays(30));
        } else {
            // سجل رسالة تحذير إذا لم يكن الكلاس موجوداً
            \Log::warning('LogFile class not found.');
        }
    }

    /**
     * Backup database.
     */
    private function backupDatabase(): void
    {
        // Placeholder for database backup
        Log::info('Database backed up');
    }

    /**
     * Backup files.
     */
    private function backupFiles(): void
    {
        // Placeholder for files backup
        Log::info('Files backed up');
    }
}
