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
        // Add phone column if missing
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('phone', 20)->nullable()->after('is_admin');
            });
        }

        // For SQLite testing environment, add triggers to enforce validation
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // Email format validation: must contain '@'
            DB::unprepared(<<<'SQL'
                    CREATE TRIGGER IF NOT EXISTS trg_users_email_validate_insert
                    BEFORE INSERT ON users
                    FOR EACH ROW
                    WHEN NEW.email NOT LIKE '%@%'
                    BEGIN
                        SELECT RAISE(ABORT, 'Invalid email format');
                    END;
                SQL);

            DB::unprepared(<<<'SQL'
                    CREATE TRIGGER IF NOT EXISTS trg_users_email_validate_update
                    BEFORE UPDATE ON users
                    FOR EACH ROW
                    WHEN NEW.email NOT LIKE '%@%'
                    BEGIN
                        SELECT RAISE(ABORT, 'Invalid email format');
                    END;
                SQL);

            // Phone format validation: either NULL or matches +[0-9]*
            DB::unprepared(<<<'SQL'
                    CREATE TRIGGER IF NOT EXISTS trg_users_phone_validate_insert
                    BEFORE INSERT ON users
                    FOR EACH ROW
                    WHEN NEW.phone IS NOT NULL AND NEW.phone NOT GLOB '+[0-9]*'
                    BEGIN
                        SELECT RAISE(ABORT, 'Invalid phone format');
                    END;
                SQL);

            DB::unprepared(<<<'SQL'
                    CREATE TRIGGER IF NOT EXISTS trg_users_phone_validate_update
                    BEFORE UPDATE ON users
                    FOR EACH ROW
                    WHEN NEW.phone IS NOT NULL AND NEW.phone NOT GLOB '+[0-9]*'
                    BEGIN
                        SELECT RAISE(ABORT, 'Invalid phone format');
                    END;
                SQL);
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_users_email_validate_insert;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_users_email_validate_update;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_users_phone_validate_insert;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_users_phone_validate_update;');
        }

        if (Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('phone');
            });
        }
    }
};
