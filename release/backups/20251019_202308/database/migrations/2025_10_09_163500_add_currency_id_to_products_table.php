<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add currency_id to products if missing
        if (! Schema::hasColumn('products', 'currency_id')) {
            Schema::table('products', function (Blueprint $table): void {
                // Use foreignId when possible; SQLite will ignore constraints on ALTER
                if (method_exists($table, 'foreignId')) {
                    $table->foreignId('currency_id')
                        ->nullable()
                        ->constrained('currencies')
                        ->onDelete('set null');
                } else {
                    $table->unsignedBigInteger('currency_id')
                        ->nullable()
                        ->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'currency_id')) {
            Schema::table('products', function (Blueprint $table): void {
                // Drop FK if exists, then the column
                try {
                    $table->dropForeign(['currency_id']);
                } catch (\Throwable $e) {
                    // Some drivers (e.g., SQLite on ALTER) may not support dropping FK here
                }
                $table->dropColumn('currency_id');
            });
        }
    }
};
