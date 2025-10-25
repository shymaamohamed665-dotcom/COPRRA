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
        if (! $this->indexExists('wishlists', 'idx_wishlists_product_id')) {
            Schema::table('wishlists', function (Blueprint $table): void {
                $table->index(['product_id'], 'idx_wishlists_product_id');
            });
        }

        if (! $this->indexExists('wishlists', 'idx_wishlists_user_id')) {
            Schema::table('wishlists', function (Blueprint $table): void {
                $table->index(['user_id'], 'idx_wishlists_user_id');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('wishlists', 'idx_wishlists_product_id')) {
            Schema::table('wishlists', function (Blueprint $table): void {
                $table->dropIndex('idx_wishlists_product_id');
            });
        }

        if ($this->indexExists('wishlists', 'idx_wishlists_user_id')) {
            Schema::table('wishlists', function (Blueprint $table): void {
                $table->dropIndex('idx_wishlists_user_id');
            });
        }
    }
};
