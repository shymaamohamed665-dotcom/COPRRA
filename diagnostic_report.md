# تقرير التشخيص (مبني على الأصول النشطة فقط)

يوثّق هذا التقرير نتائج تشغيل الأصول النشطة كما حُدّدت في `active_assets.log`، مع تسجيل العوائق التي منعت التنفيذ الكامل، وإرفاق مراجع المخرجات الخام.

## ملخص التنفيذ
- اختبارات PHPUnit عبر `composer test`:
  - الحالة: فشل التنفيذ.
  - الرسالة الدالة: `'vendor\bin\phpunit' is not recognized as an internal or external command`.
  - المرجع: `raw_outputs/raw_composer_test.log`.
- التحليل الثابت بـ `phpstan`:
  - الحالة: فشل التنفيذ.
  - الرسالة: `The module 'vendor' could not be loaded. ... CommandNotFoundException` (عدم توفر ثنائيات تحت `vendor`).
  - المرجع: `raw_outputs/raw_phpstan_output.log`.
- التحليل الثابت بـ `psalm`:
  - الحالة: فشل التنفيذ.
  - الرسالة: مماثلة لـ `phpstan` حول عدم توفر مسار `vendor`.
  - المرجع: `raw_outputs/raw_psalm_output.log`.
- محاولة قياس التغطية عبر سكربت Composer `test:coverage`:
  - الحالة: المحاولة أُجريت.
  - المرجع: `raw_outputs/raw_coverage_attempt.log`.
  - الملاحظة المتوقعة: ستفشل إذا كانت اعتمادات `vendor` غير مُثبتة أو إذا كان محرّك التغطية (PCOV/Xdebug) غير مُفعّل.

## عوائق التشغيل
- غياب مجلد الاعتمادات `vendor/` أو عدم تثبيت الحزم اللازمة:
  - الدليل: رسائل الخطأ عند تشغيل `composer test` و`phpstan` و`psalm`.
  - الأثر: يمنع تشغيل الاختبارات والأدوات النشطة.
- عدم توفر/تفعيل محرّك التغطية (PCOV أو Xdebug):
  - في حال تثبيت الاعتمادات، سيبقى قياس التغطية معطّلًا بدون أحد المحرّكين.

## التوصيات العملية لتمكين التشغيل الكامل
- تثبيت الاعتمادات:
  - نفِّذ: `composer install` في جذر المشروع.
- تشغيل الاختبارات بعد التثبيت:
  - نفِّذ: `composer test` أو تشغيل Suites محددة مثل: `composer run test:unit`, `composer run test:feature`, `composer run test:ai`, `composer run test:security`, `composer run test:performance`, `composer run test:integration`.
- تفعيل التغطية:
  - خيار 1: تثبيت وتمكين `pcov` (مفضّل للأداء).
  - خيار 2: تثبيت وتمكين `xdebug` مع وضع `coverage`.
  - بعدها: `composer run test:coverage`.
- التحليل الثابت:
  - بعد توفر `vendor`: `vendor\bin\phpstan.bat analyse -c phpstan.neon` و`vendor\bin\psalm.bat --threads=4`.

## مراجع المخرجات الخام
- قائمة سكربتات Composer: `raw_outputs/raw_composer_scripts.out`.
- اختبارات: `raw_outputs/raw_composer_test.log`.
- phpstan: `raw_outputs/raw_phpstan_output.log`.
- psalm: `raw_outputs/raw_psalm_output.log`.
- محاولة التغطية: `raw_outputs/raw_coverage_attempt.log`.

## ملاحظات إضافية
- قائمة الأصول النشطة مبنية من `phpunit.xml`, `composer.json`, `phpstan.neon`, `psalm.xml` وملفات CI تحت `.github/workflows` إضافةً إلى كل اختبارات `*Test.php` تحت `tests/`.
- كمية كبيرة من الملفات تحت `backups/…` مصنفة مهملة ولن تُشغّل ضمن هذا المسح.

— نهاية التقرير —
