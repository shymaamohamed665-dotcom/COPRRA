<?php

declare(strict_types=1);

namespace App\Services\LogProcessing;

class LogProcessingService
{
    public function __construct(
        private LogFileReader $fileReader,
        private LogLineParser $lineParser,
        private ErrorStatisticsCalculator $statisticsCalculator,
        private SystemHealthChecker $healthChecker
    ) {}

    /**
     * Process log files and extract recent errors
     *
     * @param  list<string>  $logFiles
     * @return list<array{
     *     id: string,
     *     timestamp: string,
     *     level: string,
     *     type: string,
     *     message: string,
     *     context: array<string, string>
     * }>
     */
    public function processLogFilesForRecentErrors(array $logFiles): array
    {
        $errors = [];

        foreach ($logFiles as $logFile) {
            $errors = array_merge($errors, $this->processSingleLogFile($logFile));
        }

        return $errors;
    }

    /**
     * Process error statistics for log files
     *
     * @param  list<string>  $logFiles
     * @return array{
     *     total_errors: int,
     *     critical_errors: int,
     *     errors_by_type: array<string, int>,
     *     errors_by_hour: array<string, int>,
     *     errors_by_day: array<string, int>
     * }
     */
    public function processErrorStatistics(array $logFiles): array
    {
        return $this->statisticsCalculator->calculate($logFiles);
    }

    /**
     * Get system health status
     *
     * @return array{
     *     database: array{status: string, message: string},
     *     cache: array{status: string, message: string},
     *     storage: array{status: string, message: string},
     *     memory: array{status: string, message: string},
     *     disk_space: array{status: string, message: string}
     * }
     */
    public function getSystemHealth(): array
    {
        return $this->healthChecker->checkAll();
    }

    /**
     * Get error by ID from log files
     *
     * @param  list<string>  $logFiles
     * @return array{
     *     id: string,
     *     timestamp: string,
     *     level: string,
     *     type: string,
     *     message: string,
     *     context: array<string, string>
     * }|null
     */
    public function getErrorById(array $logFiles, string $id): ?array
    {
        foreach ($logFiles as $logFile) {
            $error = $this->searchErrorInLogFile($logFile, $id);
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    /**
     * Calculate overall health from individual health checks
     *
     * @param array{
     *     database: array{status: string},
     *     cache: array{status: string},
     *     storage: array{status: string},
     *     memory: array{status: string},
     *     disk_space: array{status: string}
     * }  $health
     *
     * @psalm-return 'critical'|'healthy'|'warning'
     */
    public function calculateOverallHealth(array $health): string
    {
        $overallHealth = 'healthy';
        foreach ($health as $status) {
            if ($status['status'] === 'critical') {
                return 'critical';
            }
            if ($status['status'] === 'warning') {
                $overallHealth = 'warning';
            }
        }

        return $overallHealth;
    }

    /**
     * Process a single log file and extract errors
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
    private function processSingleLogFile(string $logFile): array
    {
        $content = $this->fileReader->readFile($logFile);
        if (empty($content)) {
            return [];
        }

        $lines = explode("\n", $content);

        return $this->extractErrorsFromLines($lines);
    }

    /**
     * Extract error lines from log content
     *
     * @param  list<string>  $lines
     * @return list<array{
     *     id: string,
     *     timestamp: string,
     *     level: string,
     *     type: string,
     *     message: string,
     *     context: array<string, string>
     * }>
     */
    private function extractErrorsFromLines(array $lines): array
    {
        $errors = [];

        foreach ($lines as $line) {
            if ($this->lineParser->isErrorLine($line)) {
                $errors[] = $this->lineParser->parseLogLine($line);
            }
        }

        return $errors;
    }

    /**
     * Search for an error in a specific log file
     *
     * @return array{
     *     id: string,
     *     timestamp: string,
     *     level: string,
     *     type: string,
     *     message: string,
     *     context: array<string, string>
     * }|null
     */
    private function searchErrorInLogFile(string $logFile, string $id): ?array
    {
        $content = $this->fileReader->readFile($logFile);
        if (empty($content)) {
            return null;
        }

        $lines = explode("\n", $content);

        return $this->findErrorInLines($lines, $id);
    }

    /**
     * Find error in array of log lines
     *
     * @param  list<string>  $lines
     * @return array{
     *     id: string,
     *     timestamp: string,
     *     level: string,
     *     type: string,
     *     message: string,
     *     context: array<string, string>
     * }|null
     */
    private function findErrorInLines(array $lines, string $id): ?array
    {
        foreach ($lines as $line) {
            if (str_contains($line, $id)) {
                return $this->lineParser->parseLogLine($line);
            }
        }

        return null;
    }
}
