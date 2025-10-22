<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add SEO meta columns to products table if missing
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table): void {
                if (! Schema::hasColumn('products', 'meta_title')) {
                    $table->string('meta_title')->nullable()->after('description');
                }
                if (! Schema::hasColumn('products', 'meta_description')) {
                    $table->text('meta_description')->nullable()->after('meta_title');
                }
                if (! Schema::hasColumn('products', 'meta_keywords')) {
                    $table->text('meta_keywords')->nullable()->after('meta_description');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table): void {
                // Guard: drop columns only if they exist
                if (Schema::hasColumn('products', 'meta_title')) {
                    $table->dropColumn('meta_title');
                }
                if (Schema::hasColumn('products', 'meta_description')) {
                    $table->dropColumn('meta_description');
                }
                if (Schema::hasColumn('products', 'meta_keywords')) {
                    $table->dropColumn('meta_keywords');
                }
            });
        }
    }
};
