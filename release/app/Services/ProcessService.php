<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ProcessResult;
use Illuminate\Support\Facades\Process;

final class ProcessService
{
    /**
     * Current processing status.
     */
    private string $status = 'idle';

    /**
     * Processed items counter.
     */
    private int $processedCount = 0;

    /**
     * Error counter.
     */
    private int $errorCount = 0;

    /**
     * Validation errors.
     *
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * Run a process command.
     *
     * @param  array<string>|string  $command
     */
    public function run(string|array $command): ProcessResult
    {
        // Set timeout for long-running commands like Pint
        $timeout = 300; // 5 minutes
        if (is_string($command) && str_contains($command, 'pint')) {
            $timeout = 300;
        }

        $result = Process::timeout($timeout)->run($command);

        // Pre-populate defaults
        $output = $result->output();
        $errorOutput = $result->errorOutput();
        $exitCode = $result->exitCode() ?? 0;

        // For git commands, stderr often contains success messages, not errors
        $isGitCommand = is_string($command) && str_starts_with($command, 'git');

        if ($isGitCommand) {
            // Check if the stderr contains success messages and normalize accordingly
            $isSuccessMessage = str_contains($errorOutput, 'Switched to a new branch') ||
                str_contains($errorOutput, 'Branch created') ||
                str_contains($errorOutput, 'successfully');

            if ($isSuccessMessage) {
                $output = $output !== '' ? $output : $errorOutput;
                $errorOutput = '';
                $exitCode = 0;
            }
        }

        return new ProcessResult(
            $exitCode,
            $output,
            $errorOutput
        );
    }

    /**
     * Process input data and return a structured result.
     *
     * @param  array<string, mixed>|null  $data
     *
     * @return array<array<array|scalar|null>|bool|string|null>
     *
     * @psalm-return array{processed: bool, error: bool, message: ''|'Invalid data provided'|'Validation failed', data: array<string, array|scalar|null>|null}
     */
    public function process(?array $data): array
    {
        if ($data === null) {
            $this->status = 'idle';
            $this->errorCount++;

            return [
                'processed' => false,
                'error' => true,
                'message' => 'Invalid data provided',
                'data' => null,
            ];
        }

        $isValid = $this->validate($data);
        if (! $isValid) {
            $this->status = 'error';
            $this->errorCount++;

            return [
                'processed' => false,
                'error' => true,
                'message' => 'Validation failed',
                'data' => null,
            ];
        }

        $cleaned = $this->clean($data);
        $this->status = 'completed';
        $this->processedCount++;

        return [
            'processed' => true,
            'error' => false,
            'message' => '',
            'data' => $cleaned,
        ];
    }

    /**
     * Get current processing status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Reset service state.
     */
    public function reset(): void
    {
        $this->status = 'idle';
        $this->errors = [];
    }

    /**
     * Get processing metrics.
     *
     * @return array{processed_count: int, error_count: int}
     */
    public function getMetrics(): array
    {
        return [
            'processed_count' => $this->processedCount,
            'error_count' => $this->errorCount,
        ];
    }

    /**
     * Validate data.
     *
     * @param  array<string, string|null>  $data
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        if (isset($data['name']) && ($data['name'] === null || $data['name'] === '')) {
            $this->errors['name'] = 'Name is required';
        }

        if (isset($data['email']) && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Email is invalid';
        }

        return $this->errors === [];
    }

    /**
     * Get validation errors.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Clean data.
     *
     * @param  array<string, scalar|array|null>  $data
     *
     * @return array<string, scalar|array|null>
     */
    public function clean(array $data): array
    {
        $cleaned = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $cleaned[$key] = trim($value);

                if ($key === 'email') {
                    $cleaned[$key] = strtolower($cleaned[$key]);
                }
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * Transform data.
     *
     * @param  array<string, scalar|array|null>  $data
     *
     * @return array<string, scalar|array|null>
     */
    public function transform(array $data): array
    {
        $transformed = [];

        foreach ($data as $key => $value) {
            $transformed[$key] = is_string($value) ? ucfirst($value) : $value;
        }

        return $transformed;
    }
}
