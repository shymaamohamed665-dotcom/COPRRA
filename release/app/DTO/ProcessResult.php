<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ProcessResult
{
    /**
     * Create a new process result instance.
     */
    public function __construct(
        /**
         * The exit code of the process.
         */
        public int $exitCode,
        /**
         * The output of the process.
         */
        public string $output,
        /**
         * The error output of the process.
         */
        public string $errorOutput
    ) {
    }

    /**
     * Get the output of the process.
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * Get the error output of the process.
     */
    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    /**
     * Check if the process was successful.
     */
    public function successful(): bool
    {
        return $this->exitCode === 0;
    }

    /**
     * Check if the process failed.
     */
    public function failed(): bool
    {
        return ! $this->successful();
    }
}
