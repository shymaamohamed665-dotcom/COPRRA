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
        if (! Schema::hasTable('custom_notifications')) {
            Schema::create('custom_notifications', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('type');
                $table->string('title');
                $table->text('message');
                $table->json('data')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->integer('priority')->default(2);
                $table->string('channel')->default('database');
                $table->string('status')->default('pending');
                $table->json('metadata')->nullable();
                $table->json('tags')->nullable();
                $table->timestamps();

                // Foreign key constraint
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                // Indexes for performance
                $table->index('user_id');
                $table->index('type');
                $table->index('status');
                $table->index('priority');
                $table->index('channel');

                $table->index('sent_at');
                $table->index('created_at');
                $table->index(['user_id', 'read_at']);
                $table->index(['user_id', 'status']);
                $table->index(['type', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_notifications');
    }
};
