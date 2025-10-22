<?php

declare(strict_types=1);

namespace App\Services\LogProcessing;

class LogFileReader
{
    /**
     * Read file content
     */
    public function readFile(string $filePath): string
    {
        $content = file_get_contents($filePath);

        return $content !== false ? $content : '';
    }

    /**
     * Get log files from storage
     *
     * @return array<string>
     *
     * @psalm-return list<non-empty-string>
     */
    public function getLogFiles(): array
    {
        $logFiles = glob(storage_path('logs/*.log'));

        return $logFiles !== false ? $logFiles : [];
    }
}
