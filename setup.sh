#!/bin/bash

# ==============================================================================
# Script لتثبيت وتكوين أدوات فحص وتحليل مشروع Laravel
# ==============================================================================

# إيقاف السكريبت فورًا في حال حدوث أي خطأ
set -e

echo "======================================="
echo "بدء تحديث قائمة الحزم وتثبيت الأدوات الأساسية..."
echo "======================================="
# تحديث قائمة الحزم وتثبيت أدوات ضرورية مثل git و zip
apt-get update
apt-get install -y git zip unzip

echo "======================================="
echo "تثبيت اعتماديات Composer..."
echo "======================================="
# التحقق من وجود ملف composer.json قبل تشغيل الأمر
if [ -f "composer.json" ]; then
    # --no-interaction: لعدم طلب أي إدخال من المستخدم
    # --prefer-dist: لتنزيل الحزم كملفات مضغوطة (أسرع)
    # --optimize-autoloader: لتحسين ملفات التحميل التلقائي للـ classes
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "ملف composer.json غير موجود، تم تخطي التثبيت."
fi

echo "======================================="
echo "تثبيت اعتماديات NPM/Node.js..."
echo "======================================="
# التحقق من وجود ملف package.json
if [ -f "package.json" ]; then
    npm install
else
    echo "ملف package.json غير موجود، تم تخطي التثبيت."
fi

echo "======================================="
echo "تجهيز إعدادات Laravel..."
echo "======================================="
# نسخ ملف .env.example إلى .env إذا لم يكن موجودًا
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "إنشاء ملف .env من .env.example..."
    cp .env.example .env
fi

# توليد مفتاح التطبيق (ضروري لعمل Laravel)
# يتم التحقق أولاً من أن APP_KEY فارغ في ملف .env
if grep -q 'APP_KEY=$' .env; then
    echo "توليد مفتاح التطبيق APP_KEY..."
    php artisan key:generate
else
    echo "مفتاح التطبيق APP_KEY موجود بالفعل."
fi

echo "======================================="
echo "تنظيف الكاش الخاص بـ Laravel..."
echo "======================================="
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "======================================="
echo "إعطاء الصلاحيات الصحيحة لمجلدات storage و bootstrap/cache..."
echo "======================================="
# هذه الخطوة حيوية لتجنب مشاكل الصلاحيات في Laravel
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "======================================="
echo "اكتمل التثبيت والتكوين بنجاح!"
echo "البيئة الآن جاهزة للفحص والتحليل."
echo "======================================="


