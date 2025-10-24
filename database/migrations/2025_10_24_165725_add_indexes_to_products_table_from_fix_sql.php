<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index([
                0 => 'category_id',
            ], 'idx_products_category_id');
            $table->index([
                0 => 'brand_id',
            ], 'idx_products_brand_id');
            $table->index([
                0 => 'store_id',
            ], 'idx_products_store_id');
            $table->index([
                0 => 'currency_id',
            ], 'idx_products_currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('idx_products_category_id');
            $table->dropIndex('idx_products_brand_id');
            $table->dropIndex('idx_products_store_id');
            $table->dropIndex('idx_products_currency_id');
        });
    }
};
