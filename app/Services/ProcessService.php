<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ProcessResult;
use Illuminate\Support\Facades\Process;

final class ProcessService
{
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

        // For git commands, stderr often contains success messages, not errors
        $isGitCommand = is_string($command) && str_starts_with($command, 'git');

        if ($isGitCommand) {
            // For git commands, check if the stderr contains success messages
            $errorOutput = $result->errorOutput();
            $isSuccessMessage = str_contains($errorOutput, 'Switched to a new branch') ||
                str_contains($errorOutput, 'Branch created') ||
                str_contains($errorOutput, 'successfully');

            if ($isSuccessMessage) {
                // Treat stderr as output for successful git commands, and force success
                $output = $result->output() ? $result->output() : $errorOutput;
                $errorOutput = '';
                $exitCode = 0; // Force success for git commands with success messages
            } else {
                $output = $result->output();
                $exitCode = $result->exitCode() ?? 0;
            }
        } else {
            $output = $result->output();
            $errorOutput = $result->errorOutput();
            $exitCode = $result->exitCode() ?? 0;
        }

        return new ProcessResult(
            $exitCode,
            $output,
            $errorOutput
        );
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
