<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_store', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->unsignedBigInteger('__COL__')->change();
            $table->unsignedBigInteger('__COL__')->change();
            $table->unsignedBigInteger('__COL__')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Original type unknown; skipping reversal for `product_id` to avoid data loss.
        // Original type unknown; skipping reversal for `store_id` to avoid data loss.
        // Original type unknown; skipping reversal for `currency_id` to avoid data loss.
    }
};
