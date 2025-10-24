<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->index([
                0 => 'product_id',
            ], 'idx_reviews_product_id');
            $table->index([
                0 => 'user_id',
            ], 'idx_reviews_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex('idx_reviews_product_id');
            $table->dropIndex('idx_reviews_user_id');
        });
    }
};
