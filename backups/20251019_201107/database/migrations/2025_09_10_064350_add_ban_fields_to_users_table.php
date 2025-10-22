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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_blocked')->default(false);
            $table->string('ban_reason')->nullable();
            $table->text('ban_description')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->timestamp('ban_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'is_blocked',
                'ban_reason',
                'ban_description',
                'banned_at',
                'ban_expires_at',
            ]);
        });
    }
};
