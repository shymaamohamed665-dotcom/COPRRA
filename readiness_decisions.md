# ملخص القرارات والإجراءات للوصول إلى الجاهزية التشغيلية

هذا المستند يوضح الخطوات العملية والقرارات التقنية المتخذة للوصول إلى بيئة جاهزة لتشغيل الأدوات، مع توثيق الأسباب خلف كل قرار.

## الخطوات المنفذة
- إنشاء مجلد `raw_outputs_v2/` لتجميع المخرجات الواقعية.
- فحص بيئة التنفيذ وتسجيل الإصدارات:
  - PHP: `8.4.13`، Composer: `2.8.12` → `raw_outputs_v2/environment_check.log`.
- تثبيت الاعتماديات:
  - تنفيذ `composer install` (مع مسار احتياطي عبر `composer.phar` إذا لزم) وتسجيل المخرجات في `raw_outputs_v2/composer_install.log`.
  - تنفيذ `composer dump-autoload -o` وتسجيل المخرجات في `raw_outputs_v2/composer_dump_autoload.log`.
- تأكيد جاهزية الاعتماديات:
  - التحقق من وجود مجلد `vendor`، وحفظ النتيجة في `raw_outputs_v2/vendor_check.txt`.
- تشغيل الأدوات:
  - PHPUnit عبر `phpunit.phar` باستخدام `phpunit.xml` → `raw_outputs_v2/phpunit_run.log`.
  - PHPStan عبر سكربت Composer `analyse:phpstan` → `raw_outputs_v2/phpstan_run.log`.
  - Psalm مباشرة عبر `php vendor/bin/psalm` لتجاوز غياب `.bat` في Windows → `raw_outputs_v2/psalm_run.log`.

## أسباب القرارات
- الاعتماد على `phpunit.phar`:
  - لضمان الاستقلال عن ملفات التنفيذ `*.bat` التي قد لا تُنشأ على Windows، وتفادي إشكاليات المسار.
- استخدام سكربت Composer لـ PHPStan:
  - لتمرير الإعدادات وملف `phpstan.neon` والخيارات الافتراضية بسلاسة.
- تشغيل Psalm عبر `php vendor/bin/psalm`:
  - بسبب غياب ملفات `psalm.bat` ضمن `vendor/bin`، ما يستلزم الاستدعاء المباشر عبر PHP.

## المخرجات التجميعية
- جميع السجلات محفوظة تحت `raw_outputs_v2/` وتم الرجوع إليها في `diagnostic_report_v2.md`.

## توصيات لاحقة
- إصلاح أخطاء PHPStan أولًا.
- تحديث Baseline لـ Psalm ومعالجة `UnusedBaselineEntry`.
- التفكير في تمكين تغطية الكود عبر Xdebug/PCOV لاحقًا.
