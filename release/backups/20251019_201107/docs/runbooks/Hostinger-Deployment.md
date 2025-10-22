# دليل نشر COPRRA على Hostinger (hPanel)

هذا الدليل يوضّح خطوات نشر مشروع Laravel/Vite على نطاق `coprra.com` عبر Hostinger، مع تنظيف الملفات/قواعد البيانات القديمة، وإعداد بيئة إنتاج آمنة.

## المتطلبات
- صلاحية دخول إلى hPanel لحساب `coprra.com` (قد يتطلب 2FA).
- تفعيل SSH (اختياري لكنه مفضّل) أو استخدام "File Manager".
- نسخة الإصدار المجهزة: `coprra-release.zip` موجودة في جذر المشروع المحلي.
- معلومات قاعدة البيانات الإنتاجية الجديدة (سننشئها في الخطوات).

## الخطوات

### 1) أخذ نسخة احتياطية ثم تنظيف الاستضافة
- افتح hPanel > Files > File Manager.
- إن وُجدت ملفات موقع قديم داخل `public_html` أو مجلد الجذر للنطاق، قم بضغطها إلى أرشيف كنسخة احتياطية ثم احذفها (حسب طلبك: لا تحتاجها).
- افتح hPanel > Databases > MySQL Databases:
  - صدّر أي قواعد بيانات قديمة كنسخة احتياطية إن لزم، ثم احذفها (حسب الطلب).

### 2) إنشاء قاعدة بيانات ومستخدم جديدين
- من MySQL Databases:
  - أنشئ قاعدة بيانات مثل: `coprra_prod`.
  - أنشئ مستخدمًا: `coprra_prod` بكلمة مرور قوية.
  - اربط المستخدم بالقاعدة مع إعطاء "All Privileges".
- احتفظ بالقيم لإعداد `.env`:
  - `DB_HOST` غالبًا `localhost`
  - `DB_DATABASE=coprrra_prod`
  - `DB_USERNAME=coprra_prod`
  - `DB_PASSWORD=********`

### 3) رفع النسخة `coprra-release.zip`
- من File Manager:
  - ارفع `coprra-release.zip` إلى المسار الخاص بـ `coprra.com`.
  - اضغط Extract لاستخراج الملفات.
- تأكد من عدم وجود رابط `public/storage` داخل الأرشيف (قمنا باستبعاده محليًا)، سنُنشئ الرابط على الاستضافة.

### 4) ضبط جذر الويب للمجلد `public`
- من hPanel > Advanced > Domains > Subdomains أو إعداد الروت للدومين:
  - تأكد أن Document Root للنطاق `coprra.com` هو مجلد `public` داخل المشروع.
  - إن لم يدعم Hostinger ذلك مباشرة، انقل محتوى `public` إلى `public_html` وابقِ بقية المشروع خارج الجذر، أو استخدم قاعدة إعادة كتابة مناسبة.

### 5) إعداد ملف البيئة `.env`
- انسخ الملف `deploy/.env.production` من الأرشيف إلى جذر المشروع باسم `.env`.
- حدّث القيم:
  - `APP_URL=https://coprra.com`
  - `DB_*` وفق بيانات الخطوة (2).
  - إعدادات البريد عبر Hostinger SMTP:
    - `MAIL_HOST=smtp.hostinger.com`
    - `MAIL_PORT=587`
    - `MAIL_USERNAME` و`MAIL_PASSWORD` لحساب البريد الرسمي.
  - أضبط `APP_DEBUG=false` و`APP_ENV=production`.

### 6) إعدادات PHP والامتدادات
- من hPanel > Advanced > PHP Configuration:
  - اختر إصدار PHP ≥ 8.2.
  - فعّل الامتدادات: `mbstring`, `openssl`, `pdo_mysql`, `curl`, `intl`, `bcmath`. وإذا كان Redis متاحًا فعّل `redis`.

### 7) إنشاء رابط التخزين وتوليد المفتاح
- إن كان لديك SSH:
  - نفّذ:
    - `php artisan key:generate --force`
    - `php artisan storage:link`
    - `php artisan migrate --force`
    - `php artisan optimize:clear && php artisan optimize`
- بدون SSH: يمكنك إنشاء رابط `public/storage` يدويًا عبر لوحة التحكم إن توفّر، وإلا فعّل SSH مؤقتًا.

### 8) جدولة المهام والصفوف
- من hPanel > Advanced > Cron Jobs:
  - أضف مهمة كل دقيقة: `php /path/to/artisan schedule:run >> /dev/null 2>&1`
- إن اعتمدت `QUEUE_CONNECTION=database`:
  - شغّل عامل الصفوف عبر خدمة دائمة (يتطلب SSH)، أو استخدم `sync` مؤقتًا.

### 9) SSL وwww/non-www
- فعّل SSL مجاني عبر hPanel > SSL.
- أضف توجيه Canonical:
  - اجعل `www.coprra.com` يعيد التوجيه إلى `coprra.com` (أو العكس) عبر إعدادات DNS/htaccess.

### 10) التحقق الصحي
- افتح:
  - `https://coprra.com/api/health`
  - `https://coprra.com/health`
- يجب أن ترى `status=healthy`

### 11) البريد والرسائل
- اختبر إرسال بريد عبر `php artisan tinker` أو endpoint عام إن وجد.
- تأكد من DKIM/SPF من إعدادات البريد في Hostinger لرفع موثوقية الإرسال.

### ملاحظات إضافية
- تم تجهيز `coprra-release.zip` متضمنًا: `app`, `bootstrap`, `config`, `public`, `resources`, `vendor`, `storage`, `artisan`, `composer.json`, `composer.lock`, و`deploy/.env.production`.
- تم بناء أصول Vite إلى `public/build`.
- على الاستضافة، نفّذ `php artisan storage:link` بعد الرفع.
- في حال عدم توفّر SSH، يمكننا ترتيب نشر بديل عبر سكربتات PHP أو استخدام Composer من hPanel إن أمكن.

## ما يتطلب صلاحياتك
- دخول hPanel لإتمام حذف الملفات/قواعد البيانات القديمة وإنشاء قاعدة جديدة.
- ضبط Document Root للنطاق.
- ملء القيم النهائية في `.env` (DB، SMTP، وغيرها).
- تشغيل أو تفعيل SSH لتنفيذ أوامر Artisan الضرورية.

عند تزويدي ببيانات الدخول المؤقتة (أو إنشاء مستخدم فرعي بصلاحيات كاملة)، سأكمل كل الخطوات المتبقية تلقائيًا وأجري تحققًا شاملاً بعد النشر.