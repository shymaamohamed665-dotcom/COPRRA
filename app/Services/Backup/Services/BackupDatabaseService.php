<?php

declare(strict_types=1);

namespace App\Services\Backup\Services;

use Exception;
use Illuminate\Support\Facades\Process;

final class BackupDatabaseService
{
    /**
     * Backup database.
     *
     * @return array{
     *     filename: string,
     *     size: int,
     *     status: string
     * }
     *
     * @throws Exception
     */
    public function backupDatabase(string $backupDir): array
    {
        $dbConfig = $this->getDatabaseCredentials();
        $filename = 'database.sql';
        $filepath = $backupDir.'/'.$filename;

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['database'],
            $filepath
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            throw new Exception('Database backup failed: '.$result->errorOutput());
        }

        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'status' => 'completed',
        ];
    }

    /**
     * Restore database.
     *
     * @param  array{filename?: string}  $dbInfo
     * @return array{status: string}
     *
     * @throws Exception
     */
    public function restoreDatabase(string $backupPath, array $dbInfo): array
    {
        $credentials = $this->getDatabaseCredentials();
        $host = $credentials['host'];
        $port = $credentials['port'];
        $username = $credentials['username'];
        $password = $credentials['password'];
        $database = $credentials['database'];

        $filename = $dbInfo['filename'] ?? 'database.sql';
        $sqlFile = $backupPath.'/'.(is_string($filename) ? $filename : 'database.sql');

        if (! file_exists($sqlFile)) {
            throw new Exception('Database backup file not found');
        }

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
            $host,
            $port,
            $username,
            $password,
            $database,
            $sqlFile
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            throw new Exception('Database restoration failed: '.$result->errorOutput());
        }

        return [
            'status' => 'completed',
        ];
    }

    /**
     * Get database credentials.
     *
     * @return array{host: string, port: string, username: string, password: string, database: string}
     */
    private function getDatabaseCredentials(): array
    {
        $dbConfig = config('database.connections.mysql');
        $dbConfigArray = is_array($dbConfig) ? $dbConfig : [];

        return [
            'host' => is_string($dbConfigArray['host'] ?? null) ? $dbConfigArray['host'] : 'localhost',
            'port' => is_string($dbConfigArray['port'] ?? null) ? $dbConfigArray['port'] : '3306',
            'username' => is_string($dbConfigArray['username'] ?? null) ? $dbConfigArray['username'] : 'root',
            'password' => is_string($dbConfigArray['password'] ?? null) ? $dbConfigArray['password'] : '',
            'database' => is_string($dbConfigArray['database'] ?? null) ? $dbConfigArray['database'] : 'database',
        ];
    }
}
