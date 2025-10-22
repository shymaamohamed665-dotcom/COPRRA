<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessHeavyOperation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * Operation identifier and payload.
     */
    private string $operation;

    private array $data;

    private int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $operation, array $data, int $userId)
    {
        $this->operation = $operation;
        $this->data = $data;
        $this->userId = $userId;
    }

    /**
     * Get the timeout value.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the tries value.
     */
    public function getTries(): int
    {
        return $this->tries;
    }

    /**
     * Get the max exceptions value.
     */
    public function getMaxExceptions(): int
    {
        return $this->maxExceptions;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        logger()->info("Starting operation: {$this->operation}");

        (match ($this->operation) {
            'generate_report' => function (): void {
                // simulate work
            },
            'process_images' => function (): void {},
            'sync_data' => function (): void {},
            'send_bulk_notifications' => function (): void {},
            'update_statistics' => function (): void {},
            'cleanup_old_data' => function (): void {},
            'export_data' => function (): void {},
            'import_data' => function (): void {},
            default => /**
             * @return never
             */
            function (): void {
                logger()->error("Unknown operation: {$this->operation}");

                throw new Exception("Unknown operation: {$this->operation}");
            },
        })();

        logger()->info("Finished operation: {$this->operation}");
    }

    /**
     * Get status for a job id.
     */
    public static function getJobStatus(string $jobId): ?array
    {
        return cache()->get("job-status-{$jobId}");
    }

    /**
     * Get statuses for a user's jobs.
     *
     * @return array<int, mixed>
     */
    public static function getUserJobStatuses(int $userId): array
    {
        return cache()->get("user-{$userId}-job-statuses", []);
    }
}
