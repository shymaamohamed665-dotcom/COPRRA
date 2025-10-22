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
        Schema::table('currencies', function (Blueprint $table): void {
            $table->integer('decimal_places')->default(2)->after('exchange_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->dropColumn('decimal_places');
        });
    }
};
