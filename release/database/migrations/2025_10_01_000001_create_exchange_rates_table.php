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
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('from_currency', 3)->index();
            $table->string('to_currency', 3)->index();
            $table->decimal('rate', 20, 10);
            $table->string('source')->default('manual'); // manual, api, etc.
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate currency pairs
            $table->unique(['from_currency', 'to_currency']);

            // Index for faster lookups
            $table->index(['from_currency', 'to_currency', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
