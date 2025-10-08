<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Jobs;

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
    private int $timeout = 300; // 5 minutes

    /**
     * The number of times the job may be attempted.
     */
    private int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    private int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

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
}
