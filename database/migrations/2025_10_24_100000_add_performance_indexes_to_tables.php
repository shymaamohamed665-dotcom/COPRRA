<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasColumns(string $table, array $columns): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }
        return true;
    }

    private function tryAddIndex(string $table, string $indexName, array $columns): void
    {
        if (!$this->hasColumns($table, $columns)) {
            return;
        }
        $cols = implode('`, `', $columns);
        $sql = "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$cols}`)";
        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            // Ignore if index already exists or other non-fatal issues
        }
    }

    private function tryDropIndex(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        $sql = "ALTER TABLE `{$table}` DROP INDEX `{$indexName}`";
        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            // Ignore if index does not exist
        }
    }

    public function up(): void
    {
        // Orders
        $this->tryAddIndex('orders', 'idx_orders_user_id', ['user_id']);

        // Order items
        $this->tryAddIndex('order_items', 'idx_order_items_order_id', ['order_id']);
        $this->tryAddIndex('order_items', 'idx_order_items_product_id', ['product_id']);

        // Products
        $this->tryAddIndex('products', 'idx_products_category_id', ['category_id']);
        $this->tryAddIndex('products', 'idx_products_brand_id', ['brand_id']);
        $this->tryAddIndex('products', 'idx_products_store_id', ['store_id']);
        $this->tryAddIndex('products', 'idx_products_currency_id', ['currency_id']);

        // Reviews
        $this->tryAddIndex('reviews', 'idx_reviews_product_id', ['product_id']);
        $this->tryAddIndex('reviews', 'idx_reviews_user_id', ['user_id']);

        // Wishlists
        $this->tryAddIndex('wishlists', 'idx_wishlists_product_id', ['product_id']);
        $this->tryAddIndex('wishlists', 'idx_wishlists_user_id', ['user_id']);

        // Price alerts
        $this->tryAddIndex('price_alerts', 'idx_price_alerts_product_id', ['product_id']);
        $this->tryAddIndex('price_alerts', 'idx_price_alerts_user_id', ['user_id']);

        // Price offers
        $this->tryAddIndex('price_offers', 'idx_price_offers_product_id', ['product_id']);

        // Price history
        $this->tryAddIndex('price_history', 'idx_price_history_product_id', ['product_id']);

        // Payments
        $this->tryAddIndex('payments', 'idx_payments_order_id', ['order_id']);

        // Product store (pivot)
        $this->tryAddIndex('product_store', 'idx_product_store_product_id', ['product_id']);
        $this->tryAddIndex('product_store', 'idx_product_store_store_id', ['store_id']);
        $this->tryAddIndex('product_store', 'idx_product_store_currency_id', ['currency_id']);
    }

    public function down(): void
    {
        // Drop in reverse order (not strictly necessary for indexes but clearer)
        $this->tryDropIndex('product_store', 'idx_product_store_currency_id');
        $this->tryDropIndex('product_store', 'idx_product_store_store_id');
        $this->tryDropIndex('product_store', 'idx_product_store_product_id');

        $this->tryDropIndex('payments', 'idx_payments_order_id');

        $this->tryDropIndex('price_history', 'idx_price_history_product_id');

        $this->tryDropIndex('price_offers', 'idx_price_offers_product_id');

        $this->tryDropIndex('price_alerts', 'idx_price_alerts_user_id');
        $this->tryDropIndex('price_alerts', 'idx_price_alerts_product_id');

        $this->tryDropIndex('wishlists', 'idx_wishlists_user_id');
        $this->tryDropIndex('wishlists', 'idx_wishlists_product_id');

        $this->tryDropIndex('reviews', 'idx_reviews_user_id');
        $this->tryDropIndex('reviews', 'idx_reviews_product_id');

        $this->tryDropIndex('products', 'idx_products_currency_id');
        $this->tryDropIndex('products', 'idx_products_store_id');
        $this->tryDropIndex('products', 'idx_products_brand_id');
        $this->tryDropIndex('products', 'idx_products_category_id');

        $this->tryDropIndex('order_items', 'idx_order_items_product_id');
        $this->tryDropIndex('order_items', 'idx_order_items_order_id');

        $this->tryDropIndex('orders', 'idx_orders_user_id');
    }
};
