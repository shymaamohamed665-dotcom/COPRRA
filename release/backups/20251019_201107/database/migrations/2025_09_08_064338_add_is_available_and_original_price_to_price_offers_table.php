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
        Schema::table('price_offers', function (Blueprint $table): void {
            $table->boolean('is_available')->default(true);
            $table->decimal('original_price', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_offers', function (Blueprint $table): void {
            $table->dropColumn(['is_available', 'original_price']);
        });
    }
};
