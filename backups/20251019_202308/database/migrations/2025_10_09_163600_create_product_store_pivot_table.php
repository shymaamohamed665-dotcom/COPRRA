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
        Schema::create('product_store', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->decimal('price', 8, 2);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'store_id']);
            $table->index(['product_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_store');
    }
};
