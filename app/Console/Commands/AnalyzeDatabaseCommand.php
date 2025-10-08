<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class AnalyzeDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:analyze';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database performance and provide insights';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 Analyzing database performance...');

        $this->checkSlowQueryLog();
        $this->checkQueryCache();
        $this->displayTopLargestTables();
        $this->checkForMissingIndexes();

        $this->newLine();
        $this->info('✅ Database analysis completed!');

        return Command::SUCCESS;
    }

    /**
     * Get MySQL variable value.
     *
     * @param  string  $variableName  The name of the MySQL variable
     * @param  string  $default  Default value if variable not found
     * @return string The value of the variable
     */
    private function getMySQLVariable(string $variableName, string $default = 'OFF'): string
    {
        try {
            $result = DB::select('SHOW VARIABLES LIKE ?', [$variableName]);
            if (isset($result[0]->Value) && is_string($result[0]->Value)) {
                return $result[0]->Value;
            }
        } catch (\Exception $e) {
            $this->warn("Could not retrieve MySQL variable: {$variableName}. Error: ".$e->getMessage());
        }

        return $default;
    }

    /**
     * Check the slow query log status.
     */
    private function checkSlowQueryLog(): void
    {
        $value = $this->getMySQLVariable('slow_query_log');
        $status = $value === 'ON' ? '✓ Enabled' : '✗ Disabled';
        $this->line("  Slow query log: {$status}");
    }

    /**
     * Check the query cache status.
     */
    private function checkQueryCache(): void
    {
        try {
            $value = $this->getMySQLVariable('query_cache_type');
            $this->line('  Query cache: '.$value);
        } catch (\Exception $e) {
            $this->line('  Query cache: Not available (MySQL 8.0+)');
        }
    }

    /**
     * Display the top 10 largest tables.
     */
    private function displayTopLargestTables(): void
    {
        $tableSizes = $this->executeDatabaseQuery("
            SELECT
                table_name AS 'Table',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
                table_rows AS 'Rows'
            FROM information_schema.TABLES
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ");

        if ($tableSizes !== null) {
            $this->newLine();
            $this->line('  Top 10 largest tables:');
            $this->displayTableData($tableSizes);
        }
    }

    /**
     * Display table data in a formatted table.
     *
     * @param  array<int, object|array>  $data  The data to display
     */
    private function displayTableData(array $data): void
    {
        $this->table(
            ['Table', 'Size (MB)', 'Rows'],
            array_map(fn ($row) => (array) $row, $data)
        );
    }

    /**
     * Check for tables without indexes.
     */
    /**
     * Get the current database name from configuration.
     */
    private function getDatabaseName(): string
    {
        return config('database.connections.mysql.database');
    }

    /**
     * Execute a database query with the current database name.
     *
     * @param  string  $query  The SQL query to execute
     * @return array<int, object|array>|null The query results
     */
    private function executeDatabaseQuery(string $query): ?array
    {
        return DB::select($query, [$this->getDatabaseName()]);
    }

    private function checkForMissingIndexes(): void
    {
        $this->newLine();
        $this->line('  Checking for missing indexes...');

        $tablesWithoutIndexes = $this->executeDatabaseQuery("
            SELECT DISTINCT
                t.table_name
            FROM information_schema.TABLES t
            LEFT JOIN information_schema.STATISTICS s
                ON t.table_schema = s.table_schema
                AND t.table_name = s.table_name
            WHERE t.table_schema = ?
                AND t.table_type = 'BASE TABLE'
                AND s.index_name IS NULL
        ");

        if ($tablesWithoutIndexes !== null) {
            $this->warn('  ⚠ Tables without indexes:');
            $this->displayTablesWithoutIndexes($tablesWithoutIndexes);
        } else {
            $this->line('  ✓ All tables have indexes');
        }
    }

    /**
     * Display tables without indexes.
     *
     * @param  array<int, object>  $tables  Array of table objects
     */
    private function displayTablesWithoutIndexes(array $tables): void
    {
        foreach ($tables as $table) {
            if (is_object($table) && isset($table->table_name) && is_string($table->table_name)) {
                $this->line('    - '.$table->table_name);
            }
        }
    }
}
