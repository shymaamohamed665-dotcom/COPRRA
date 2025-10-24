<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_offers', function (Blueprint $table): void {
            $table->index([
                0 => 'product_id',
            ], 'idx_price_offers_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('price_offers', function (Blueprint $table): void {
            $table->dropIndex('idx_price_offers_product_id');
        });
    }
};
