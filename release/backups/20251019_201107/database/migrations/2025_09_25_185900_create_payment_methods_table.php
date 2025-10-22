<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('gateway');
            $table->string('type'); // stripe, paypal, bank_transfer, etc.
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['gateway', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
