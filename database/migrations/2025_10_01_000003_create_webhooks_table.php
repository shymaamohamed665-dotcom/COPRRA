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
        Schema::create('webhooks', function (Blueprint $table): void {
            $table->id();
            $table->string('store_identifier', 50)->index();
            $table->string('event_type', 50)->index(); // price_update, stock_update, product_update
            $table->string('product_identifier', 100);
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('payload');
            $table->string('signature', 255)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['store_identifier', 'event_type']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->string('action', 50);
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('webhook_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
    }
};
