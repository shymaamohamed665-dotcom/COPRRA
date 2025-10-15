<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add order_date column if missing
        if (! Schema::hasColumn('orders', 'order_date')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dateTime('order_date')->nullable()->after('delivered_at');
            });
        }

        // For SQLite testing environment, add triggers to enforce datetime format
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // Enforce YYYY-MM-DD HH:MM:SS format when set
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS trg_orders_order_date_validate_insert
                BEFORE INSERT ON orders
                FOR EACH ROW
                WHEN NEW.order_date IS NOT NULL AND NEW.order_date NOT GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]'
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid order_date format');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS trg_orders_order_date_validate_update
                BEFORE UPDATE ON orders
                FOR EACH ROW
                WHEN NEW.order_date IS NOT NULL AND NEW.order_date NOT GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]'
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid order_date format');
                END;
            SQL);
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_orders_order_date_validate_insert;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_orders_order_date_validate_update;');
        }

        if (Schema::hasColumn('orders', 'order_date')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('order_date');
            });
        }
    }
};
