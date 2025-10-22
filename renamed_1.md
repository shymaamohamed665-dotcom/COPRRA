# دليل التشغيل والصيانة (محدّث)

تاريخ الإعداد: 2025-10-19
المسار: `C:\Users\Gaser\Desktop\COPRRA`

القسم التنفيذي:
- يوضّح هذا الدليل كيفية إدارة وتشغيل منظومة الاختبارات والأتمتة الداخلية، بما يشمل تشغيل الاختبارات، قياس التغطية، التعامل مع الإصلاحات الذاتية، والاندماج مع CI/CD.

1) المتطلبات البيئية:
- PHP 8.4.13
- Composer
- PHPUnit 12.4.1 (ملف الإعداد: `phpunit.xml`)
- سائق التغطية (موصى: PCOV أو Xdebug) لقياس التغطية.

2) تشغيل الاختبارات:
- تشغيل كامل: `php .\phpunit.phar`
- تشغيل مجموعة محددة:
  - Unit: `php .\phpunit.phar --testsuite Unit`
  - Feature: `php .\phpunit.phar --testsuite Feature`
  - AI: `php .\phpunit.phar --testsuite AI`
  - Security: `php .\phpunit.phar --testsuite Security`
  - Performance: `php .\phpunit.phar --testsuite Performance`
  - Integration: `php .\phpunit.phar --testsuite Integration`
- حفظ المخرجات: `php .\phpunit.phar *> test_results\phpunit_latest.out`

3) قياس التغطية:
- PCOV (مفضل للأداء):
  - `extension=pcov` و `pcov.enabled=1` في `php.ini`.
  - تشغيل: `php .\phpunit.phar --coverage-text` أو `--coverage-html coverage`.
- Xdebug (بديل شامل):
  - `zend_extension=xdebug` و `xdebug.mode=coverage`.
  - تشغيل بنفس الأوامر.
- مخرجات التغطية المقترحة:
  - نصي: `test_results\\coverage_text.out`
  - HTML: `coverage/`

4) منظومة الإصلاح الذاتي (تشغيليًا):
- عند فشل اختبار/تحذير، يسجَّل الحدث وتُولَّد تذكرة إصلاح آليًا.
- المشغّل الذكي يقترح التصحيحات (Patch Proposals) دون دمج تلقائي؛ يتطلب مراجعة بشرية.
- بعد تطبيق التصحيح: إعادة تشغيل الاختبارات + تحديث التقارير.
- تقييد التغييرات لكل دفعة (batch) لضمان السيطرة التشغيلية.

5) التكامل مع CI/CD:
- مصفوفة تنفيذ عبر إصدارات PHP ومنصات التشغيل.
- خطوة تمكين سائق التغطية قبل تشغيل PHPUnit.
- نشر تقارير التغطية كـ artifacts وفشل عند عدم استيفاء العتبة.
- سياسات صارمة قائمة: `failOnWarning`, `failOnRisky`, `stopOnFailure`.

6) المراقبة والتسجيل:
- حفظ سجلات التشغيل في `logs/` مع تدوير ذكي.
- مقاييس زمن التنفيذ والذاكرة من مخرجات PHPUnit؛ جمع دورية وتحليل الاتجاهات.

7) الأمن:
- إدارة الأسرار (Secrets) عبر مخزن آمن مع تدوير مفاتيح.
- فحص سري (Secret Scanning) ضمن الـ CI للتأكد من عدم تسرب بيانات حساسة.

8) الاستجابة للحوادث:
- مسار سريع لعزل تغييرات الإصلاح الذاتي عند ظهور أثر جانبي.
- خطة rollback واضحة واستعادة الوضع المستقر.

9) التحقق بعد النشر:
- إعادة تشغيل الاختبارات كاملة في بيئة staging/production-like.
- مراجعة مؤشرات الأداء والأمان بعد كل إصلاح مدمج.

10) مهام مجدولة:
- تشغيل ليلي للحزمة الكاملة + تقارير تغطية.
- فحص أسبوعي لمستوى التحذيرات/الDeprecated.

11) معروفات ومحددات:
- حالياً، لا تتوفر تغطية بسبب غياب السائق؛ يلزم تمكين PCOV/Xdebug.
- اختبارات الأداء/الأمن عددها أقل نسبيًا؛ يُنصح بالتوسيع.

12) أوامر مفيدة:
- تشغيل كامل وحفظ المخرجات: `php .\phpunit.phar *> test_results\phpunit_latest.out`
- تشغيل تغطية نصية بعد تمكين السائق: `php .\phpunit.phar --coverage-text *> test_results\coverage_text.out`
- تشغيل Suite معين: `php .\phpunit.phar --testsuite Unit`

هذا الدليل يُكمل التقرير التشغيلي التفصيلي السابق ويُركّز على إدارة الاختبارات والتغطية والأتمتة الداخلية ضمن سياق CI/CD.
