<?php

declare(strict_types=1);

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
        // Drop the existing table if it exists
        Schema::dropIfExists('price_offers');

        // Create the table with the correct structure
        Schema::create('price_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('product_sku')->nullable();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->decimal('price', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('product_url')->nullable();
            $table->string('affiliate_url')->nullable();
            $table->boolean('in_stock')->default(true);
            $table->integer('stock_quantity')->nullable();
            $table->string('condition')->default('new');
            $table->decimal('rating', 3, 1)->nullable();
            $table->integer('reviews_count')->default(0);
            $table->string('image_url')->nullable();
            $table->json('specifications')->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('original_price', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_offers');
    }
};
