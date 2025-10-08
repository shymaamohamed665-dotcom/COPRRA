<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\DB;

final class DatabaseOptimizer
{
    public function __construct(private readonly OutputStyle $output) {}

    public function optimizeDatabase(): void
    {
        $this->output->info('🗄️  Optimizing database...');

        try {
            $tables = DB::select('SHOW TABLES');
            $database = config('database.connections.mysql.database');
            $tableKey = is_string($database) ? "Tables_in_{$database}" : 'Tables_in_';

            $optimized = 0;
            foreach ($tables as $table) {
                if (is_object($table) && isset($table->$tableKey) && is_string($table->$tableKey)) {
                    $tableName = $table->$tableKey;

                    try {
                        DB::statement("OPTIMIZE TABLE `{$tableName}`");
                        $optimized++;
                    } catch (\Exception $e) {
                        $this->output->warn("  - Could not optimize table `{$tableName}`: {$e->getMessage()}");
                    }
                }
            }

            $this->output->line("  ✓ Optimized {$optimized} tables");
        } catch (\Exception $e) {
            $this->output->warn('  ✗ Failed to optimize database: '.$e->getMessage());
        }

        $this->output->newLine();
    }

    public function analyzeDatabasePerformance(): void
    {
        $this->output->info('📊 Analyzing database performance...');

        try {
            $this->checkSlowQueryLog();
            $this->checkQueryCache();
            $this->displayTopLargestTables();
            $this->checkForMissingIndexes();
        } catch (\Exception $e) {
            $this->output->warn('  ✗ Failed to analyze database: '.$e->getMessage());
        }

        $this->output->newLine();
    }

    private function checkSlowQueryLog(): void
    {
        $slowQueries = DB::select('SHOW VARIABLES LIKE "slow_query_log"');
        $value = 'OFF';
        if (isset($slowQueries[0]->Value) && is_string($slowQueries[0]->Value)) {
            $value = $slowQueries[0]->Value;
        }
        $status = $value === 'ON' ? '✓ Enabled' : '✗ Disabled';
        $this->output->line("  Slow query log: {$status}");
    }

    private function checkQueryCache(): void
    {
        try {
            $cacheStatus = DB::select('SHOW VARIABLES LIKE "query_cache_type"');
            $value = 'OFF';
            if (isset($cacheStatus[0]->Value) && is_string($cacheStatus[0]->Value)) {
                $value = $cacheStatus[0]->Value;
            }
            $this->output->line('  Query cache: '.$value);
        } catch (\Exception $e) {
            $this->output->line('  Query cache: Not available (MySQL 8.0+)');
        }
    }

    private function displayTopLargestTables(): void
    {
        $database = config('database.connections.mysql.database');
        $tableSizes = DB::select("
            SELECT
                table_name AS 'Table',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
                table_rows AS 'Rows'
            FROM information_schema.TABLES
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ", [$database]);

        if ($tableSizes !== null) {
            $this->output->newLine();
            $this->output->line('  Top 10 largest tables:');
            $this->output->table(
                ['Table', 'Size (MB)', 'Rows'],
                array_map(fn ($row) => (array) $row, $tableSizes)
            );
        }
    }

    private function checkForMissingIndexes(): void
    {
        $this->output->newLine();
        $this->output->line('  Checking for missing indexes...');

        $database = config('database.connections.mysql.database');
        $tablesWithoutIndexes = DB::select("
            SELECT DISTINCT
                t.table_name
            FROM information_schema.TABLES t
            LEFT JOIN information_schema.STATISTICS s
                ON t.table_schema = s.table_schema
                AND t.table_name = s.table_name
            WHERE t.table_schema = ?
                AND t.table_type = 'BASE TABLE'
                AND s.index_name IS NULL
        ", [$database]);

        if ($tablesWithoutIndexes !== null) {
            $this->output->warn('  ⚠ Tables without indexes:');
            foreach ($tablesWithoutIndexes as $table) {
                if (is_object($table) && isset($table->table_name) && is_string($table->table_name)) {
                    $this->output->line('    - '.$table->table_name);
                }
            }
        } else {
            $this->output->line('  ✓ All tables have indexes');
        }
    }
}
