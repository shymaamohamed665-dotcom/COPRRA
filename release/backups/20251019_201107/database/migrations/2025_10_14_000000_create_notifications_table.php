<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                // Some tests expect a direct user_id column in addition to the morphs
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->morphs('notifiable'); // notifiable_type, notifiable_id
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        } else {
            // Ensure user_id column exists for tests that rely on it
            if (! Schema::hasColumn('notifications', 'user_id')) {
                Schema::table('notifications', function (Blueprint $table): void {
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
