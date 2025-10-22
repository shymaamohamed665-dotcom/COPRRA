<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('notifications', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (Schema::hasColumn('notifications', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
