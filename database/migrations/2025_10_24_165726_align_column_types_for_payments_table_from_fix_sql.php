<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->unsignedBigInteger('__COL__')->change();
        });
    }

    public function down(): void
    {
        // Original type unknown; skipping reversal for `order_id` to avoid data loss.
    }
};
