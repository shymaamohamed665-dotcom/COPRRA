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
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 50)->index(); // price_comparison, product_view, search, etc.
            $table->string('event_name', 100);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('store_id')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id', 100)->nullable()->index();
            $table->timestamps();

            // Indexes for performance
            $table->index(['event_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
