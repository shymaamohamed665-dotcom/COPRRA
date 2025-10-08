<?php

declare(strict_types=1);

namespace App\Services\LogProcessing;

class ErrorStatisticsCalculator
{
    public function __construct(
        private LogFileReader $fileReader,
        private LogLineParser $lineParser
    ) {}

    /**
     * Calculate error statistics for log files
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
    public function calculate(array $logFiles): array
    {
        $stats = [
            'total_errors' => 0,
            'critical_errors' => 0,
            'errors_by_type' => [],
            'errors_by_hour' => [],
            'errors_by_day' => [],
        ];

        foreach ($logFiles as $logFile) {
            $this->processLogFileForStats($stats, $logFile);
        }

        return $stats;
    }

    /**
     * Process a single log file for statistics
     *
     * @param array{
     *     total_errors: int,
     *     critical_errors: int,
     *     errors_by_type: array<string, int>,
     *     errors_by_hour: array<string, int>,
     *     errors_by_day: array<string, int>
     * } $stats
     */
    private function processLogFileForStats(array &$stats, string $logFile): void
    {
        $content = $this->fileReader->readFile($logFile);
        if (empty($content)) {
            return;
        }

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if ($this->lineParser->isErrorLine($line)) {
                $this->updateErrorStats($stats, $line);
            }
        }
    }

    /**
     * Update error statistics
     *
     * @param array{
     *     total_errors: int,
     *     critical_errors: int,
     *     errors_by_type: array<string, int>,
     *     errors_by_hour: array<string, int>,
     *     errors_by_day: array<string, int>
     * } $stats
     */
    private function updateErrorStats(array &$stats, string $line): void
    {
        $error = $this->lineParser->parseLogLine($line);

        $stats['total_errors']++;

        if (str_contains($line, 'CRITICAL')) {
            $stats['critical_errors']++;
        }

        $this->updateErrorTypeStats($stats, $error);
        $this->updateTimeBasedStats($stats, $error);
    }

    /**
     * Update error type statistics
     *
     * @param array{
     *     total_errors: int,
     *     critical_errors: int,
     *     errors_by_type: array<string, int>,
     *     errors_by_hour: array<string, int>,
     *     errors_by_day: array<string, int>
     * } $stats
     */
    private function updateErrorTypeStats(array &$stats, array $error): void
    {
        $type = is_string($error['type'] ?? '') ? $error['type'] : 'Unknown';
        $stats['errors_by_type'][$type] = ($stats['errors_by_type'][$type] ?? 0) + 1;
    }

    /**
     * Update time-based statistics
     *
     * @param array{
     *     total_errors: int,
     *     critical_errors: int,
     *     errors_by_type: array<string, int>,
     *     errors_by_hour: array<string, int>,
     *     errors_by_day: array<string, int>
     * } $stats
     */
    private function updateTimeBasedStats(array &$stats, array $error): void
    {
        $timestampValue = $error['timestamp'] ?? null;
        $timestamp = is_string($timestampValue) ? strtotime($timestampValue) : time();
        $validTimestamp = $timestamp === false ? time() : $timestamp;

        $hour = date('H', $validTimestamp);
        $stats['errors_by_hour'][$hour] = ($stats['errors_by_hour'][$hour] ?? 0) + 1;

        $day = date('Y-m-d', $validTimestamp);
        $stats['errors_by_day'][$day] = ($stats['errors_by_day'][$day] ?? 0) + 1;
    }
}
