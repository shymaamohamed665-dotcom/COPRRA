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
            foreach ($results as $rowObj) {
                $row = (array) $rowObj;
                if (isset($row['name']) && $row['name'] === $indexName) {
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
        // Create indexes only if missing to avoid duplicate errors in SQLite/MySQL
        if (! $this->indexExists('products', 'idx_products_category_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->index(['category_id'], 'idx_products_category_id');
            });
        }

        if (! $this->indexExists('products', 'idx_products_brand_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->index(['brand_id'], 'idx_products_brand_id');
            });
        }

        if (! $this->indexExists('products', 'idx_products_store_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->index(['store_id'], 'idx_products_store_id');
            });
        }

        if (! $this->indexExists('products', 'idx_products_currency_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->index(['currency_id'], 'idx_products_currency_id');
            });
        }
    }

    public function down(): void
    {
        // Drop indexes only if they exist, to avoid errors on down in sqlite
        if ($this->indexExists('products', 'idx_products_category_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropIndex('idx_products_category_id');
            });
        }

        if ($this->indexExists('products', 'idx_products_brand_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropIndex('idx_products_brand_id');
            });
        }

        if ($this->indexExists('products', 'idx_products_store_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropIndex('idx_products_store_id');
            });
        }

        if ($this->indexExists('products', 'idx_products_currency_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropIndex('idx_products_currency_id');
            });
        }
    }
};
