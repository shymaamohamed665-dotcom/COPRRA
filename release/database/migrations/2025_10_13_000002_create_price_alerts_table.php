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
        // Guard against duplicate creation if the table already exists.
        if (! Schema::hasTable('price_alerts')) {
            Schema::create('price_alerts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('target_price', 10, 2);
                $table->decimal('current_price', 10, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->boolean('is_active')->default(true);
                $table->boolean('repeat_alert')->default(false);
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamp('last_triggered_at')->nullable();
                $table->integer('trigger_count')->default(0);
                $table->timestamps();

                // Indexes
                $table->index(['user_id', 'is_active']);
                $table->index(['product_id', 'is_active']);
                $table->index('target_price');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop if exists to avoid errors in different environments.
        Schema::dropIfExists('price_alerts');
    }
};
