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
        if (! Schema::hasColumn('stores', 'affiliate_code')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->string('affiliate_code', 100)->nullable()->after('affiliate_base_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stores', 'affiliate_code')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->dropColumn('affiliate_code');
            });
        }
    }
};
