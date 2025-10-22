# ملخص التعديلات — run_all_tests_automated_enhanced.sh

- النسخ الاحتياطي: سيتم أخذ نسخة احتياطية قبل النقل إلى الحاوية (خارج السكريبت)، وتُحفظ داخل `reports/` بطابع زمني ISO.
- إضافة دوال خدمية: `ts_iso`, `log_info`, `log_warn`, `log_error`, `send_notification`, `safe_exit`, `update_checkpoint`, `clear_checkpoint`, `read_checkpoint_index`, `check_global_limit_or_exit`, `local_repair_attempt`.
- `attempt_auto_fix()`: يستدعي صراحة مُصلّح Python (`ai_tools/python_auto_fixer.py`) ومُصلّح PHP (`ai_tools/php_auto_fixer.php`) إن وُجدا، ويكتب نتيجة الإصلاح في JSON.
- عدّاد إصلاح تلقائي عام مع حد أقصى (`GLOBAL_MAX_REPAIR_ATTEMPTS`) وآلية خروج آمن عند تجاوزه.
- آلية Checkpoint عبر ملف `.run_all_tests_checkpoint` للاستئناف، وتحديثه بعد كل عنصر مكتمل.
- إعادة تشغيل العناصر الفاشلة بعد نجاح الإصلاح التلقائي.
- قياس الموارد: وقت البدء والانتهاء؛ واعتماد `/usr/bin/time -v` إن توفر لالتقاط قمّة الذاكرة (مع قيمة مبسطة).
- تقارير HTML وJSON ملونة ودقيقة، لكل عنصر JSON مستقل، مع تجميع تلقائي إلى ملفي تقرير شاملين.
- إشعارات حسب الأحداث عبر Slack (إن توفر `SLACK_WEBHOOK_URL`) أو التسجيل المحلي.
- فتح سجل الأخطاء تلقائيًا عند الفشل غير القابل للإصلاح قبل الخروج.
- قفل بسيط للكتابة باستخدام مجلد مؤقت `.run_all_tests_lockdir` لتجنب التداخل.

المخرجات:
- `reports/*.log`, `reports/*.json`, `reports/*_snippet.html`, `reports/report_*.html`, `reports/report_*.json`.
- سجلات ورسائل عربية بطوابع زمنية ISO.

ملاحظات التشغيل:
- السكريبت يكتشف الأدوات المتاحة (phpunit/pest/artisan/composer/php-lint) ويشغّلها إن توفرت.
- في حال غياب أدوات المُصلّح، يتم تسجيل التحذير مع الاستمرار.
- يمكن الاستئناف عبر: `RESUME=1 ./run_all_tests_automated_enhanced.sh`.
