# تقرير تغطية الاختبارات (أولي)

تاريخ الإعداد: 2025-10-19
المسار: `C:\Users\Gaser\Desktop\COPRRA`

ملخص:
- تم تشغيل PHPUnit بنجاح: `OK (2071 tests, 5454 assertions)`.
- محاولة توليد تغطية عبر `phpdbg` و/أو السائق الافتراضي فشلت برسالة: `No code coverage driver available`.
- لا يوجد امتداد `Xdebug` أو `PCOV` محمّل حاليًا (`php -m` لا يعرضهما).

ما تم تنفيذه:
- أمر التشغيل القياسي: `php .\phpunit.phar`
- محاولة تغطية نصية: `phpdbg -qrr .\phpunit.phar --coverage-text` -> نتج التحذير المذكور.

السبب الجذري:
- PHPUnit 10+/12 يتطلب سائق تغطية صريح (Xdebug أو PCOV). في غيابهما، لا يمكن قياس التغطية.

خطة التفعيل السريعة للتغطية:
1) خيار PCOV (أخفّ وأسرع من Xdebug للتغطية):
   - حمّل `php_pcov.dll` المطابق لإصدار PHP 8.4 ولمعاملات البناء (Non Thread Safe/Thread Safe) على ويندوز.
   - أضف إلى `php.ini`:
     - `extension=pcov`
     - `pcov.enabled=1`
   - أعد تشغيل أي خدمات تستخدم PHP إن وجدت.
   - شغّل: `php .\phpunit.phar --coverage-text` أو `--coverage-html coverage`.

2) خيار Xdebug (أوسع لكنه أثقل للأداء):
   - حمّل `php_xdebug.dll` المطابق لإصدار PHP.
   - أضف إلى `php.ini`:
     - `zend_extension=xdebug`
     - `xdebug.mode=coverage`
     - `xdebug.start_with_request=no`
   - شغّل: `php .\phpunit.phar --coverage-text` أو `--coverage-html coverage`.

3) خيار phpdbg (بديل تقليدي):
   - إذا كان `phpdbg` مُمكّن بتغطية في توزيعة PHP الحالية:
     - `phpdbg -qrr .\vendor\bin\phpunit --coverage-html coverage`.
   - في حال استمرار رسالة "لا يوجد سائق تغطية" فهذا يعني أن التوزيعة الحالية لا تدعم تغطية عبر phpdbg.

نطاق القياس المقترح بعد التفعيل:
- تغطية عامة للمشروع + تقارير مفصّلة للوحدات الحرجة (منطق الإصلاح الذاتي، أدوات الأتمتة الداخلية، خدمات الأمن والأداء).
- حفظ التقارير في:
  - نصي: `test_results\\coverage_text.out`
  - HTML: `coverage/`
  - Clover: `coverage/clover.xml` (للاندماج مع CI ولوحات مثل SonarQube).

متطلبات CI:
- إضافة خطوة في CI تقوم بتثبيت/تمكين PCOV أو Xdebug.
- نشر تغطية HTML كـ Artifact.
- فشل CI إن انخفضت التغطية عن العتبة المتفق عليها (مثلاً 70% كبداية).

مخرجات حالية:
- لم يتم قياس التغطية بسبب غياب السائق.
- جاهزون لإعادة القياس فور تمكين PCOV/Xdebug.
