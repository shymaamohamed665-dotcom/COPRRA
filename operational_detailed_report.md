**ملخص تشغيلي**
- جاهزية التشغيل والنشر مؤكدة بعد مرور كل الاختبارات والتحليلات.
- المسارات الحرجة (استعادة كلمة المرور والنسخ الاحتياطي) تعمل وفق الإصلاحات المدمجة.
- تقارير نهائية واقتراحات محفوظة؛ هذا الملف يُعد دليل تشغيل مفصّل.

**المتطلبات البيئية**
- PHP `8.2` مع الامتدادات: `pdo_mysql`, `zip`, `redis`, `opcache`.
- Composer `2.x`, Node.js `>=18`, npm `>=9` أو `pnpm` مكافئ.
- MySQL `8.x` (أو متوافق)، Redis `6.x` أو `7.x`.
- متغيرات البيئة الأساسية: `APP_ENV`, `APP_KEY`, `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- على ويندوز: فعّل `ZipArchive` كبديل لـ `tar` عند الحاجة للنسخ الاحتياطي.

**خطوات النشر (Production)**
- سحب الشفرة من الفرع `main` أو الإصدار الموقّع.
- تثبيت الاعتماديات:
  - `composer install --no-dev --prefer-dist --no-progress`
  - `npm ci && npm run build`
- تجهيز Laravel:
  - ضبط المفتاح: `php artisan key:generate` (مرة واحدة لكل بيئة)
  - تحسين الأداء: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
  - ترحيل القاعدة: `php artisan migrate --force`
  - إعداد التخزين: `php artisan storage:link`
  - إعادة تشغيل العمال: `php artisan queue:restart`
- التحقق الصحي:
  - نفّذ فحص نقطة الصحة: `curl -fsS https://your-domain/api/health` وتأكد أن الاستجابة `200`.

**التحقق قبل النشر (Pre-Deployment)**
- اختبارات: `./vendor/bin/phpunit --colors=never`
- التحليل الثابت: `vendor/bin/phpstan analyse`، `vendor/bin/psalm --no-cache`، `vendor/bin/phpmd app text phpmd.xml`
- فحوص الأمن: `composer audit`، `npm audit --omit=dev`، `gitleaks detect`، `vendor/bin/security-checker`.
- لا يتم النشر عند أي فشل في الخطوات أعلاه.

**النسخ الاحتياطي والاستعادة**
- النسخ الاحتياطي:
  - مسار النسخ: `backups/` داخل الجذر.
  - على لينكس: استخدم `tar` لضغط المجلدات.
  - على ويندوز: استخدم `ZipArchive` لتفادي تعارضات `tar`.
- الاستعادة:
  - أوقف العمال مؤقتًا.
  - فك الضغط إلى المسارات المناسبة (`storage`, `public/uploads`, أي مجلدات بيانات).
  - تأكد من صلاحيات الكتابة للمجلدات: `storage/`, `bootstrap/cache`.
  - أعد تشغيل الخدمات والعمال.

**إدارة استعادة كلمة المرور**
- في الاختبار: `MAIL_MAILER=array` لمنع اتصال SMTP.
- في الإنتاج: تأكد من ضبط `MAIL_*` وقيم `from` بشكل صحيح.
- المسارات:
  - إرسال الرابط: `POST /password/email`
  - نموذج الإدخال: `GET /password/reset`
  - تنفيذ التحديث: `POST /password/reset`

**تشغيل CI/CD**
- Workflows رئيسية:
  - `ci.yml`: بناء، تحليل، اختبارات، تغطية، رفع Artifacts.
  - `security-audit.yml`: فحوص الأمن اليومية وعلى Push/PR.
  - `deployment.yml`: نشر على `main` أو عبر تشغيل يدوي.
- موحّدة الإصدارات: ثبّت إصدار PHP عبر جميع الـ Workflows لتوحيد النتائج.
- إدارة الأسرار: استخدم `GitHub Secrets` لتمرير بيانات قاعدة البيانات والبريد.

**تشغيل Docker**
- تشغيل متعدد الحاويات:
  - `docker compose -f docker/docker-compose.scale.yml up -d`
- خدمات رئيسية:
  - `nginx` كموازِن تحميل، `app1/app2/app3` تطبيق، `mysql`, `redis`, `queue-worker`، `scheduler`.
- ضبط الشبكات والحجوم:
  - راقب الحجوم المشتركة مثل `storage/logs` لمنع تعارضات بين الحاويات.
- أوامر مفيدة:
  - سجلات: `docker compose logs --tail=200 app1`
  - حالة: `docker compose ps`
  - إيقاف: `docker compose down`

**المراقبة والسجلات**
- سجلات Nginx: `docker/logs/app/`
- أخطاء PHP: `storage/logs/php-error.log`
- سجلات Laravel: `storage/logs/laravel.log`
- مؤشرات: استخدام Prometheus/Grafana (مجلد `docker/`)، راقب `CPU`, `Memory`, `HTTP 5xx`.

**الأمن وإدارة الأسرار**
- لا تحفظ مفاتيح SSL أو أسرار ضمن المستودع.
- استخدم bind volumes أو أسرار CI لحقن القيم في وقت التشغيل.
- فحوص دورية: `gitleaks`, `Enlightn`, `Trivy` على النظام والاعتماديات.

**الاستجابة للحوادث (Incident Response)**
- تحديد النطاق: هل المشكلة في `app` أم `nginx` أم `db` أم `workers`؟
- جمع الأدلة: سجلات التطبيق والويب وقاعدة البيانات.
- إجراءات سريعة:
  - إعادة تشغيل العمال: `php artisan queue:restart`
  - تفريغ كاش config/route: `php artisan config:clear && php artisan route:clear`
  - تراجع إصدار: نفّذ نشر النسخة السابقة أو استعادة من النسخة الاحتياطية.
- التصعيد: إشعار فريق البنية/الأمن عند وجود مؤشرات اختراق أو تسريب.

**قائمة تحقق بعد النشر**
- صحة `/api/health`، فحص صفحات رئيسية.
- اختبار تدفق تسجيل الدخول واستعادة كلمة المرور.
- تشغيل مهمة صفّ تجريبية والتأكد من التنفيذ.
- مراجعة السجلات لعدم وجود أخطاء حرجة.

**المهام المجدولة**
- Laravel Scheduler:
  - تشغيل: `php artisan schedule:work` أو `php artisan schedule:run`
  - داخل Compose: خدمة `scheduler` مفعّلة.
- اضبط المنطقة الزمنية عبر `APP_TIMEZONE` إن لزم.

**مشاكل معروفة ومعالجات**
- ويندوز وحذف المجلدات: تم تحسين `deleteDirectory` لدعم الروابط الرمزية وضبط الصلاحيات قبل `rmdir`.
- تعارض `tar` على ويندوز: استخدم `ZipArchive` كبديل.
- زمن `fastcgi_read_timeout`: راقب الوقت لتفادي إخفاء أخطاء طويلة.

**أوامر مفيدة**
- تشغيل الاختبارات: `./vendor/bin/phpunit --colors=never`
- فحص الأمن Composer: `composer audit`
- فحص الأمن npm: `npm audit --omit=dev`
- تحسين Laravel: `php artisan optimize`
- الهجرة: `php artisan migrate --force`
- العامل: `php artisan queue:work --tries=3`
- ربط التخزين: `php artisan storage:link`

**ملاحظات ختامية**
- حافظ على توحيد الإصدارات في CI لضمان ثبات النتائج.
- وثّق أي تغييرات تشغيلية جديدة في هذا الملف وفي `docs/runbooks/`.
- نفّذ مراجعة دورية للضوابط الأمنية والأداء كل ربع سنة.