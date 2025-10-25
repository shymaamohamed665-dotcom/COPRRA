<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Add indexes
            $table->index(['product_id'], 'idx_price_history_product_id');
            $table->index(['recorded_at'], 'idx_price_history_recorded_at');
            $table->index(['product_id', 'recorded_at'], 'idx_price_history_product_recorded');

            // Foreign key constraint (if products table exists)
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
