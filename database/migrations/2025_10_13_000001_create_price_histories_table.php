<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('price_histories')) {
            Schema::create('price_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->decimal('price', 10, 2);
                $table->timestamp('captured_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
