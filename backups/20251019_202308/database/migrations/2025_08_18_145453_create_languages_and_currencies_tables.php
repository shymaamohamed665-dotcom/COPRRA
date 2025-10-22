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
        // جدول اللغات
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 5)->unique(); // ar, en, fr, etc.
            $table->string('name'); // Arabic, English, French
            $table->string('native_name'); // العربية, English, Français
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr');
            $table->boolean('is_active')->default(true);

            // هذا هو السطر الذي أضفناه
            $table->boolean('is_default')->default(false);

            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // جدول العملات
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 3)->unique(); // USD, EUR, SAR, etc.
            $table->string('name'); // US Dollar, Euro, Saudi Riyal
            $table->string('symbol', 10); // $, €, ر.س
            $table->decimal('exchange_rate', 10, 4)->default(1.0000); // سعر الصرف مقابل الدولار
            $table->boolean('is_active')->default(true);

            // أضفنا هذا السطر هنا أيضًا للاحتياط
            $table->boolean('is_default')->default(false);

            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // جدول ربط اللغات بالعملات
        Schema::create('language_currency', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('language_id')->constrained()->onDelete('cascade');
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->boolean('is_default')->default(false); // العملة الافتراضية لهذه اللغة
            $table->timestamps();

            $table->unique(['language_id', 'currency_id']);
        });

        // جدول إعدادات المستخدم للغة والعملة
        Schema::create('user_locale_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // للزوار غير المسجلين
            $table->foreignId('language_id')->constrained();
            $table->foreignId('currency_id')->constrained();
            $table->string('ip_address', 45)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locale_settings');
        Schema::dropIfExists('language_currency');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('languages');
    }
};
