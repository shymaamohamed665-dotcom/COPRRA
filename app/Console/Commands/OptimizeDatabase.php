<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * @psalm-suppress PropertyNotSetInConstructor, UnusedClass
 */
final class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database performance by adding indexes and analyzing tables';

    public function handle(): int
    {
        $this->info('Starting database optimization...');

        $this->addIndexes();
        $this->analyzeAndOptimizeTables();

        Artisan::call('cache:clear');

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
                DB::statement($index);
                $this->line('✓ Added index: '.substr($index, 0, 50).'...');
            } catch (\Exception $e) {
                $this->warn('⚠ Index may already exist: '.$e->getMessage());
            }
        }
    }

    private function analyzeAndOptimizeTables(): void
    {
        $this->info('Analyzing and optimizing tables...');
        $tables = $this->getTables();

        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
                $this->line("✓ Analyzed table: {$table}");
                DB::statement("OPTIMIZE TABLE {$table}");
                $this->line("✓ Optimized table: {$table}");
            } catch (\Exception $e) {
                $this->warn("⚠ Failed to analyze or optimize {$table}: ".$e->getMessage());
            }
        }
    }

    /**
     * @return array<int, string>
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
