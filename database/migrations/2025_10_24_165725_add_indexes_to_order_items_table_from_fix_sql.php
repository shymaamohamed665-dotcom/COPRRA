<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            // Check if index doesn't exist before creating it
            if (! $this->indexExists('order_items', 'idx_order_items_order_id')) {
                $table->index(['order_id'], 'idx_order_items_order_id');
            }

            if (! $this->indexExists('order_items', 'idx_order_items_product_id')) {
                $table->index(['product_id'], 'idx_order_items_product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            if ($this->indexExists('order_items', 'idx_order_items_order_id')) {
                $table->dropIndex('idx_order_items_order_id');
            }

            if ($this->indexExists('order_items', 'idx_order_items_product_id')) {
                $table->dropIndex('idx_order_items_product_id');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select('
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = ? 
            AND table_name = ? 
            AND index_name = ?
        ', [$databaseName, $table, $indexName]);

        if (empty($result)) {
            return false;
        }

        /** @var object{count: int} $firstResult */
        $firstResult = $result[0];

        return $firstResult->count > 0;
    }
};
