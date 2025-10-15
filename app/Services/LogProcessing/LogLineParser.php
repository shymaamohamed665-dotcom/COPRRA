<?php

declare(strict_types=1);

namespace App\Services\LogProcessing;

class LogLineParser
{
    /**
     * Check if a line contains an error
     */
    public function isErrorLine(string $line): bool
    {
        return str_contains($line, 'ERROR') || str_contains($line, 'CRITICAL');
    }

    /**
     * Parse log line
     *
     * @return (string|string[])[]
     *
     * @psalm-return array{id: string, timestamp: string, level: string, type: string, message: string, context: array<string, string>}
     */
    public function parseLogLine(string $line): array
    {
        // Basic log parsing - this would be more sophisticated in production
        $parts = explode(' ', $line, 4);

        return [
            'id' => uniqid(),
            'timestamp' => $parts[0] ?? '',
            'level' => $parts[1] ?? 'ERROR',
            'type' => $this->extractErrorType($line),
            'message' => $parts[3] ?? $line,
            'context' => $this->extractContext($line),
        ];
    }

    /**
     * Extract error type from log line
     */
    private function extractErrorType(string $line): string
    {
        if (str_contains($line, 'Database')) {
            return 'Database';
        }
        if (str_contains($line, 'Redis')) {
            return 'Cache';
        }
        if (str_contains($line, 'Validation')) {
            return 'Validation';
        }
        if (str_contains($line, 'Authentication')) {
            return 'Authentication';
        }
        if (str_contains($line, 'Authorization')) {
            return 'Authorization';
        }

        return 'General';
    }

    /**
     * Extract context from log line
     *
     * @return (array|null|object|scalar)[]
     *
     * @psalm-return array<string, array|null|object|scalar>
     */
    private function extractContext(string $line): array
    {
        // Extract JSON context if present
        if (preg_match('/\{.*\}/', $line, $matches)) {
            /** @var array<string, string|int|float|bool|array|object|null> $result */
            $result = json_decode($matches[0], true);

            return is_array($result) ? $result : [];
        }

        return [];
    }
}
