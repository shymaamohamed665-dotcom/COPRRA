<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points');
            $table->string('type'); // earned, redeemed, expired, bonus
            $table->string('source'); // purchase, review, referral, etc.
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_points');
    }
};
