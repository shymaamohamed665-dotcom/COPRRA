<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('points_required');
            $table->enum('type', ['discount', 'free_shipping', 'gift', 'cashback']);
            $table->json('value'); // discount percentage, amount, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('usage_limit')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'points_required']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
