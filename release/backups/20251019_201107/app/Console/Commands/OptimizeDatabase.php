<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor, UnusedClass
 */
final class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:optimize';

    /**
     * The console command description.
     */
    protected $description = 'Optimize database performance by adding indexes and analyzing tables';

    private readonly DatabaseManager $database;

    public function __construct(DatabaseManager $database)
    {
        parent::__construct();
        $this->database = $database;
    }

    public function handle(): int
    {
        $this->info('Starting database optimization...');

        $this->addIndexes();
        $this->analyzeAndOptimizeTables();

        $this->call('cache:clear');

        $this->info('Database optimization completed!');

        return 0;
    }

    private function addIndexes(): void
    {
        $this->info('Adding database indexes...');

        $indexes = [
            'ALTER TABLE products ADD INDEX idx_category_price (category_id, price)',
            'ALTER TABLE products ADD INDEX idx_brand_rating (brand_id, rating)',
            'ALTER TABLE products ADD INDEX idx_created_at (created_at)',
            'ALTER TABLE orders ADD INDEX idx_user_status (user_id, status)',
            'ALTER TABLE orders ADD INDEX idx_created_at (created_at)',
            'ALTER TABLE order_items ADD INDEX idx_product_quantity (product_id, quantity)',
            'ALTER TABLE user_behaviors ADD INDEX idx_user_action (user_id, action)',
            'ALTER TABLE user_behaviors ADD INDEX idx_created_at (created_at)',
            'ALTER TABLE user_points ADD INDEX idx_user_type (user_id, type)',
            'ALTER TABLE user_points ADD INDEX idx_expires_at (expires_at)',
        ];

        foreach ($indexes as $index) {
            try {
                $this->database->statement($index);
                $this->line('✓ Added index: '.substr($index, 0, 50).'...');
            } catch (\Throwable $exception) {
                $this->warn('⚠ Index may already exist: '.$exception->getMessage());
            }
        }
    }

    private function analyzeAndOptimizeTables(): void
    {
        $this->info('Analyzing and optimizing tables...');
        $tables = $this->getTables();

        foreach ($tables as $table) {
            try {
                $this->database->statement("ANALYZE TABLE {$table}");
                $this->line("✓ Analyzed table: {$table}");
                $this->database->statement("OPTIMIZE TABLE {$table}");
                $this->line("✓ Optimized table: {$table}");
            } catch (\Throwable $exception) {
                $this->warn("⚠ Failed to analyze or optimize {$table}: ".$exception->getMessage());
            }
        }
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{'products', 'orders', 'order_items', 'users', 'user_behaviors', 'user_points', 'payments'}
     */
    private function getTables(): array
    {
        return [
            'products',
            'orders',
            'order_items',
            'users',
            'user_behaviors',
            'user_points',
            'payments',
        ];
    }
}
