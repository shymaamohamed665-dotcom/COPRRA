# تقرير تدقيق شامل لمنظومة الذكاء الاصطناعي وعمليات الجودة

**ملخص تنفيذي**

- واجهة تحكم إدارية للذكاء الاصطناعي موجودة في `resources/views/admin/ai-control-panel.blade.php` وتوفّر تحليل نصوص، تصنيف منتجات، وتوليد توصيات. لكنّ الكونترولر `AIControlPanelController.php` فارغ حالياً، لذا جميع نقاط الاستدعاء من الواجهة ستفشل.
- واجهات API عامة تعمل عبر `AIController` الذي يستهلك `AIService` ويقدّم `/api/ai/analyze` و`/api/ai/classify-product`.
- لا توجد مهام مجدولة لتشغيل `StrictQualityAgent` أو `ContinuousQualityMonitor` رغم توفرهما وخضوعهما لاختبارات.
- محاولات تشغيل Gitleaks وPHPUnit وnpm tests فشلت بخطأ (خروج غير صفري)، والمخرجات محفوظة مسبقاً.
- التحليلات الثابتة (ESLint/Stylelint/PHPStan/Psalm/PHPMD) موثّقة تفصيلياً في `reports/analysis_audit_report_detailed.txt` بدون أي تعديل على الشيفرة.

**نطاق التدقيق**

- مراجعة الواجهات الإدارية والواجهات البرمجية، الإعدادات المجدولة، وأوامر CLI ذات الصلة.
- قراءة الملفات الأساسية: `routes/web.php`، `AIControlPanelController.php`، `AIController.php`، `AIService.php`، `app/Console/Kernel.php`، وأمر `AgentProposeFixCommand.php`، بالإضافة إلى عرض الشجرة ذات الصلة.
- الاعتماد على مخرجات التحليلات السابقة المحفوظة داخل `reports/`.

**اكتشافات رئيسية**

- واجهة تحكم الذكاء الاصطناعي الإدارية:
    - الواجهة الأمامية تستدعي نقاطاً مثل `GET /admin/ai/status` و`POST /admin/ai/analyze-text` و`POST /admin/ai/classify-product` و`POST /admin/ai/recommendations`.
    - الكونترولر `app/Http/Controllers/Admin/AIControlPanelController.php` فارغ؛ أي استدعاء سيؤدي إلى خطأ في التنفيذ.
- واجهات API العامة:
    - `AIController` يطبّق نقاط `/api/ai/analyze` و`/api/ai/classify-product` ويستدعي `AIService` بنجاح، مع التحقق من المدخلات وإرجاع JSON.
- الخدمات والأنظمة:
    - `AIService` ينسّق بين `AIRequestService` و`AITextAnalysisService` و`AIImageAnalysisService` لتقديم التحليلات.
    - خدمات المراقبة والجودة: `ContinuousQualityMonitor` و`StrictQualityAgent` موجودان مع اختبارات مرافقة، لكن دون تكامل فعلي عبر واجهات أو جدولة.

**المسارات والواجهات**

- `routes/web.php`:
    - تجميع مسارات إدارية تحت `prefix('admin')->name('admin.')`، وبداخلها `prefix('ai')` لمسارات لوحة الذكاء الاصطناعي.
    - تعريف المسارات للإدارة والمنتجات والفئات، إلخ.
- `resources/views/admin/ai-control-panel.blade.php`:
    - واجهة عربية تعتمد `fetch` إلى `admin/ai/*` وتعرض النتائج/الأخطاء.
- `app/Http/Controllers/Admin/AIControlPanelController.php`:
    - فارغ؛ يجب إضافة: `index`, `analyzeText`, `classifyProduct`, `generateRecommendations`, `analyzeImage`, `getStatus`.
- `routes/api.php`:
    - نقاط `POST /api/ai/analyze` و`POST /api/ai/classify-product` تعمل عبر `AIController`.

**الجدولة وعمليات CI/CD**

- `app/Console/Kernel.php` يحدّد مهاماً مجدولة للصيانة والنسخ الاحتياطي وتحسينات SEO ومعالجة webhooks وتحديثات الأسعار.
- لا توجد مهام مجدولة لاستدعاء `StrictQualityAgent` أو `ContinuousQualityMonitor`.
- أمر CLI `agent:propose-fix` في `app/Console/Commands/AgentProposeFixCommand.php` ينفذ آلية اقتراح إصلاحات عبر فروع وPull Requests، لكنه لا يستدعي وكلاء الجودة أو المراقبة.

**مشاكل التحليلات والاختبارات**

- Gitleaks: التنفيذ أعطى خروجاً غير صفري، رغم حفظ النتائج.
- PHPUnit: فشل التنفيذ عبر `php phpunit.phar -c phpunit.xml` بخروج غير صفري.
- npm tests: فشل التنفيذ، قد يرتبط بإصدار Node/NPM أو سكربتات `package.json`.
- PHPStan/Psalm/PHPMD/Stylelint: كما ورد في `reports/analysis_audit_report_detailed.txt`، هناك قضايا أنواع، تعقيد مرتفع، قواعد SCSS غير مفعّلة، وممارسات غير مرغوبة.

**توصيات عملية**

- تفعيل لوحة الذكاء الاصطناعي الإدارية:
    - تطبيق طرق `AIControlPanelController` لتتوافق مع الواجهة:
        - `index`: إرجاع `view('admin.ai-control-panel')`.
        - `getStatus`: فحص جاهزية الخدمات (مثلاً `config('ai')`، مفاتيح، توفر الشبكة) وإرجاع JSON.
        - `analyzeText`/`classifyProduct`/`generateRecommendations`/`analyzeImage`: التفويض إلى `AIService` وإرجاع JSON موحّد الشكل.
    - أو ربط الواجهة الإدارية مباشرة بنقاط API العامة لتقليل الازدواجية.
- الجدولة:
    - إضافة مهام مجدولة لتشغيل `StrictQualityAgent` أو `ContinuousQualityMonitor` مع بوابة إعدادات مثل `config('ai.monitor.enabled')`، وتسجيل النتائج في `logs/agent/`.
- ضبط التحليلات:
    - Stylelint: تمكين SCSS أو إضافة `stylelint-scss`، وتخصيص قاعدة `at-rule-no-unknown` بشكل مناسب، ومراجعة `selector-no-qualifying-type` حسب أسلوب CSS المعتمد.
    - PHPStan/Psalm: التصريح بجنيركس في الـ Collections، استخدام المقارنات الصارمة `===/!==`، إزالة تدقيق النوع الزائد، وإعادة ضبط الثقة في PHPDoc وفق الحاجة.
    - PHPMD: إزالة معامل الكبت `@` لصالح `try/catch` مع تسجيل واضح، وتفكيك `StorageManagementService` لتقليل التعقيد، وإزالة الطرق غير المستخدمة.
- تثبيت البيئة:
    - التأكد من توافق إصدارات PHP/Extensions و`vendor/` مع `phpunit.xml`، وإعادة تشغيل اختبارات محددة أولاً (وحدات AI) قبل التوسّع.
    - مراجعة نسخ Node/NPM ومحتوى `package.json` ثم إعادة تشغيل `npm test`.
    - إعادة تشغيل Gitleaks بالإصدار/المسار الصحيح لضمان نجاح حفظ التقارير.

**خطة أسبوعية مقترحة**

- اليوم 1–2: تطبيق الكونترولر الإداري وربط الطرق بـ`AIService`، اختبار الواجهة سريعاً.
- اليوم 3: إضافة مهام مجدولة لمراقبة الجودة، بوابة إعدادات وتعقب سجلات.
- اليوم 4: معالجة توقيعات الأنواع والقضايا التي طرفها PHPStan/Psalm.
- اليوم 5: استقرار PHPUnit (تبعيات/إعدادات)، البدء باختبارات AI وCQ بعد نجاح الوحدة.
- اليوم 6: استقرار npm tests عبر ضبط الإصدارات والسكربتات.
- اليوم 7: تشغيل Gitleaks مجدداً وجمع تقارير موحّدة، وتوثيق التحسينات.

**ملحق: مسارات وملفات مهمة**

- الواجهة: `resources/views/admin/ai-control-panel.blade.php`
- مسارات الويب: `routes/web.php`
- كونترولر إداري: `app/Http/Controllers/Admin/AIControlPanelController.php`
- واجهات API: `routes/api.php`, `app/Http/Controllers/API/AIController.php`
- الخدمات: `app/Services/AI/AIService.php`، وخدمات فرعية داخل `app/Services/AI/Services/`
- المجدول: `app/Console/Kernel.php`
- أمر الإصلاحات: `app/Console/Commands/AgentProposeFixCommand.php`
- تقرير التحليلات التفصيلي: `reports/analysis_audit_report_detailed.txt`

> ملاحظة: جميع النتائج الحالية تعكس الوضع دون أي تعديل على الشيفرة. عند الموافقة يمكن تنفيذ الإصلاحات تدريجياً وفق خطة العمل أعلاه مع الحفاظ على اتساق النمط والمعايير.
