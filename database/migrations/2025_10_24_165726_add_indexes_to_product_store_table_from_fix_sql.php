<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_store', function (Blueprint $table): void {
            $table->index([
                0 => 'product_id',
            ], 'idx_product_store_product_id');
            $table->index([
                0 => 'store_id',
            ], 'idx_product_store_store_id');
            $table->index([
                0 => 'currency_id',
            ], 'idx_product_store_currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_store', function (Blueprint $table): void {
            $table->dropIndex('idx_product_store_product_id');
            $table->dropIndex('idx_product_store_store_id');
            $table->dropIndex('idx_product_store_currency_id');
        });
    }
};
