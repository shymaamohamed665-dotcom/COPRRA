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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                // Add encrypted fields for sensitive data
                if (! Schema::hasColumn('users', 'encrypted_phone')) {
                    $table->text('encrypted_phone')->nullable()->after('email_verified_at');
                }
                if (! Schema::hasColumn('users', 'encrypted_address')) {
                    $table->text('encrypted_address')->nullable()->after('encrypted_phone');
                }
                if (! Schema::hasColumn('users', 'encrypted_notes')) {
                    $table->text('encrypted_notes')->nullable()->after('encrypted_address');
                }
            });
        }

        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table): void {
                // Add encrypted fields for store sensitive data
                if (! Schema::hasColumn('stores', 'encrypted_contact_person')) {
                    $table->text('encrypted_contact_person')->nullable()->after('contact_person');
                }
                if (! Schema::hasColumn('stores', 'encrypted_contact_email')) {
                    $table->text('encrypted_contact_email')->nullable()->after('contact_email');
                }
                if (! Schema::hasColumn('stores', 'encrypted_contact_phone')) {
                    $table->text('encrypted_contact_phone')->nullable()->after('contact_phone');
                }
                if (! Schema::hasColumn('stores', 'encrypted_address')) {
                    $table->text('encrypted_address')->nullable()->after('address');
                }
                if (! Schema::hasColumn('stores', 'encrypted_api_credentials')) {
                    $table->text('encrypted_api_credentials')->nullable()->after('api_credentials');
                }
            });
        }

        if (Schema::hasTable('price_offers')) {
            Schema::table('price_offers', function (Blueprint $table): void {
                // Add encrypted fields for offer sensitive data
                if (! Schema::hasColumn('price_offers', 'encrypted_offer_url')) {
                    $table->text('encrypted_offer_url')->nullable()->after('offer_url');
                }
                if (! Schema::hasColumn('price_offers', 'encrypted_notes')) {
                    $table->text('encrypted_notes')->nullable()->after('notes');
                }
            });
        }

        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table): void {
                // Add encrypted fields for review sensitive data
                if (! Schema::hasColumn('reviews', 'encrypted_user_email')) {
                    $table->text('encrypted_user_email')->nullable()->after('user_email');
                }
                if (! Schema::hasColumn('reviews', 'encrypted_contact_info')) {
                    $table->text('encrypted_contact_info')->nullable()->after('contact_info');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'encrypted_phone',
                'encrypted_address',
                'encrypted_notes',
            ]);
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn([
                'encrypted_contact_person',
                'encrypted_contact_email',
                'encrypted_contact_phone',
                'encrypted_address',
                'encrypted_api_credentials',
            ]);
        });

        Schema::table('price_offers', function (Blueprint $table): void {
            $table->dropColumn([
                'encrypted_offer_url',
                'encrypted_notes',
            ]);
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn([
                'encrypted_user_email',
                'encrypted_contact_info',
            ]);
        });
    }
};
