<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $results = $connection->select("PRAGMA index_list('".$table."')");
            foreach ($results as $row) {
                if ((isset($row->name) && $row->name === $indexName) || (isset($row['name']) && $row['name'] === $indexName)) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $results = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

            return ! empty($results);
        }

        return false;
    }

    public function up(): void
    {
        if (! $this->indexExists('product_store', 'idx_product_store_product_id')) {
            Schema::table('product_store', function (Blueprint $table): void {
                $table->index(['product_id'], 'idx_product_store_product_id');
            });
        }

        if (! $this->indexExists('product_store', 'idx_product_store_store_id')) {
            Schema::table('product_store', function (Blueprint $table): void {
                $table->index(['store_id'], 'idx_product_store_store_id');
            });
        }

        if (! $this->indexExists('product_store', 'idx_product_store_currency_id')) {
            Schema::table('product_store', function (Blueprint $table): void {
                $table->index(['currency_id'], 'idx_product_store_currency_id');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('product_store', 'idx_product_store_product_id')) {
            Schema::table('product_store', function (Blueprint $table): void {
                $table->dropIndex('idx_product_store_product_id');
            });
        }

        if ($this->indexExists('product_store', 'idx_product_store_store_id')) {
            Schema::table('product_store', function (Blueprint $table): void {
                $table->dropIndex('idx_product_store_store_id');
            });
        }

        if ($this->indexExists('product_store', 'idx_product_store_currency_id')) {
            Schema::table('product_store', function (Blueprint $table): void {
                $table->dropIndex('idx_product_store_currency_id');
            });
        }
    }
};
