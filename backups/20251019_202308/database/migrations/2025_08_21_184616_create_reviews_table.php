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
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('content');
            $table->tinyInteger('rating')->unsigned()->comment('Rating from 1 to 5');
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->json('helpful_votes')->nullable()->comment('Array of user IDs who found this helpful');
            $table->integer('helpful_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'is_approved']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
