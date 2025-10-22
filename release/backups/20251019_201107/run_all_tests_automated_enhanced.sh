#!/usr/bin/env sh

# سكريبت مُحسّن لتشغيل الاختبارات والأدوات تلقائيًا مع إصلاح تلقائي ومحاولة استئناف
# اللغة: العربية القياسية
# جميع المخرجات والتقارير تُحفظ في /var/www/html/reports/
# يحافظ على المنطق الأساسي عبر اكتشاف الأدوات المتاحة وتشغيلها بشكل آمن.

set -eu

# إعدادات عامة
WORKDIR="/var/www/html"
REPORTS_DIR="$WORKDIR/reports"
LOGFILE="$REPORTS_DIR/run_all_tests_automated_enhanced.log"
ERROR_LOG="$WORKDIR/storage/logs/laravel.log"
CHECKPOINT_FILE="$WORKDIR/.run_all_tests_checkpoint"
LOCK_DIR="$WORKDIR/.run_all_tests_lockdir"
DIFF_PATCH_FILE_BASENAME="script_changes_diff"
SUMMARY_MD="$WORKDIR/ENHANCED_SCRIPT_SUMMARY.md"
REF_FILE="$WORKDIR/القائمة النهائية للاختبارات والادوات.txt"

# حدود الإصلاح التلقائي العامة
GLOBAL_REPAIR_ATTEMPTS=0
GLOBAL_MAX_REPAIR_ATTEMPTS=${GLOBAL_MAX_REPAIR_ATTEMPTS:-5}

# وظائف مساعدة
ts_iso() {
  # UTC ISO8601
  date -u +%Y-%m-%dT%H:%M:%SZ
}

ensure_dirs() {
  mkdir -p "$REPORTS_DIR"
}

log_info() {
  ensure_dirs
  echo "[$(ts_iso)] INFO: $*" | tee -a "$LOGFILE" >/dev/null
}

log_warn() {
  ensure_dirs
  echo "[$(ts_iso)] WARN: $*" | tee -a "$LOGFILE" >/dev/null
}

log_error() {
  ensure_dirs
  echo "[$(ts_iso)] ERROR: $*" | tee -a "$LOGFILE" >/dev/null
}

with_lock() {
  # قفل بسيط عبر مجلد خاص. إن وُجد، ننتظر حتى اختفائه لثوانٍ محدودة.
  i=0
  while [ -d "$LOCK_DIR" ] && [ "$i" -lt 50 ]; do
    i=$((i+1))
    sleep 0.1
  done
  mkdir -p "$LOCK_DIR"
  "$@"
  rmdir "$LOCK_DIR" 2>/dev/null || true
}

# مساعدات للأوامر والأدوات
resolve_composer_cmd() {
  cmd="$*"
  if command -v composer >/dev/null 2>&1; then
    echo "$cmd"
  elif [ -f "$WORKDIR/composer.phar" ]; then
    # استبدال composer بـ php composer.phar للحاوية
    echo "php $WORKDIR/composer.phar ${cmd#composer }"
  else
    echo "$cmd"
  fi
}

validate_executable() {
  name="$1"; cmd="$2"; status="FOUND"; exists=0
  first_word=$(printf "%s" "$cmd" | awk '{print $1}')
  case "$cmd" in
    composer*)
      if command -v composer >/dev/null 2>&1 || [ -f "$WORKDIR/composer.phar" ]; then
        exists=1; cmd=$(resolve_composer_cmd "$cmd")
      fi
      ;;
    php*)
      if command -v php >/dev/null 2>&1; then exists=1; fi
      ;;
    node*|npm*)
      if command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1; then exists=1; fi
      ;;
    *)
      if command -v "$first_word" >/dev/null 2>&1; then exists=1; fi
      ;;
  esac
  [ "$exists" -eq 0 ] && status="MISSING"
  printf "%s:%s\n" "$status" "$cmd"
}

safe_exit() {
  code=${1:-1}
  msg=${2:-"خروج آمن"}
  log_error "سيتم الإنهاء: $msg (code=$code)"
  if [ -f "$ERROR_LOG" ]; then
    log_error "عرض آخر 200 سطر من سجل الأخطاء: $ERROR_LOG"
    tail -n 200 "$ERROR_LOG" | sed 's/^/[ERR] /' | tee -a "$LOGFILE" >/dev/null || true
  else
    log_warn "لم يتم العثور على سجل الأخطاء عند الخروج"
  fi
  exit "$code"
}

send_notification() {
  # إرسال إشعار إلى Slack إن توفر SLACK_WEBHOOK_URL، وإلا فقط نسجل
  event=${1:-unknown}
  detail=${2:-""}
  payload=$(printf '{"timestamp":"%s","event":"%s","detail":"%s"}' "$(ts_iso)" "$event" "$detail")
  if command -v curl >/dev/null 2>&1 && [ -n "${SLACK_WEBHOOK_URL:-}" ]; then
    curl -fsS -X POST -H 'Content-Type: application/json' -d "$payload" "$SLACK_WEBHOOK_URL" >/dev/null 2>&1 || true
    log_info "أُرسل إشعار Slack: $event"
  else
    log_info "إشعار: $event — التفاصيل: $detail"
  fi
}

update_checkpoint() {
  # حفظ حالة آخر عنصر مكتمل
  idx=$1
  name=$2
  with_lock sh -c "echo \"{\"timestamp\":\"$(ts_iso)\",\"last_completed_index\":$idx,\"last_completed_name\":\"$name\"}\" > \"$CHECKPOINT_FILE\""
}

clear_checkpoint() {
  rm -f "$CHECKPOINT_FILE" 2>/dev/null || true
}

read_checkpoint_index() {
  # إرجاع رقم آخر عنصر مكتمل أو -1
  if [ -f "$CHECKPOINT_FILE" ]; then
    awk -F":" '/last_completed_index/{gsub(/[^0-9]/,""); print $2}' "$CHECKPOINT_FILE" 2>/dev/null || echo -1
  else
    echo -1
  fi
}

check_global_limit_or_exit() {
  if [ "$GLOBAL_REPAIR_ATTEMPTS" -ge "$GLOBAL_MAX_REPAIR_ATTEMPTS" ]; then
    safe_exit 2 "تجاوز حد محاولات الإصلاح التلقائي ($GLOBAL_MAX_REPAIR_ATTEMPTS)"
  fi
}

local_repair_attempt() {
  # زيادة العداد العالمي وتسجيل
  GLOBAL_REPAIR_ATTEMPTS=$((GLOBAL_REPAIR_ATTEMPTS+1))
  log_warn "محاولة إصلاح تلقائي رقم $GLOBAL_REPAIR_ATTEMPTS من $GLOBAL_MAX_REPAIR_ATTEMPTS"
}

attempt_auto_fix() {
  # يستدعي أدوات إصلاح تلقائي Python وPHP إن وجدت
  item_name="$1"
  item_log="$2"
  local_repair_attempt

  fix_json="$REPORTS_DIR/auto_fix_${item_name}_$(ts_iso).json"
  tmp_out="$REPORTS_DIR/auto_fix_${item_name}_out.txt"
  rc=1

  # Python fixer
  if command -v python3 >/dev/null 2>&1 && [ -f "$WORKDIR/ai_tools/python_auto_fixer.py" ]; then
    log_info "استدعاء مُصلّح Python لعُنصر: $item_name"
    python3 "$WORKDIR/ai_tools/python_auto_fixer.py" --item "$item_name" --log "$item_log" --out "$fix_json" >/dev/null 2>"$tmp_out" || true
  else
    log_warn "أداة مُصلّح Python غير متاحة — سيتم تخطيها"
  fi

  # PHP fixer
  if command -v php >/dev/null 2>&1 && [ -f "$WORKDIR/ai_tools/php_auto_fixer.php" ]; then
    log_info "استدعاء مُصلّح PHP لعُنصر: $item_name"
    php "$WORKDIR/ai_tools/php_auto_fixer.php" "$item_name" "$item_log" "$fix_json" >/dev/null 2>>"$tmp_out" || true
  else
    log_warn "أداة مُصلّح PHP غير متاحة — سيتم تخطيها"
  fi

  # تحديد النجاح من خلال وجود fix_json واحتوائه على حالة success:true
  if [ -f "$fix_json" ] && grep -q '"success"\s*:\s*true' "$fix_json" 2>/dev/null; then
    log_info "نجح الإصلاح التلقائي للعُنصر: $item_name"
    rc=0
  else
    log_warn "لم ينجح الإصلاح التلقائي للعُنصر: $item_name"
    rc=1
  fi

  echo "$rc"
}

measure_and_run() {
  # قياس الوقت و (عند توفر /usr/bin/time -v) موارد العملية
  item_name="$1"
  cmd="$2"

  item_log="$REPORTS_DIR/${item_name}_$(ts_iso).log"
  html_snippet="$REPORTS_DIR/${item_name}_snippet.html"
  start_ts_ms=$(date -u +%s)
  cpu_avg="n/a"; cpu_peak="n/a"; mem_avg="n/a"; mem_peak="n/a"

  log_info "بدء تنفيذ: $item_name — الأمر: $cmd"

  if [ -x /usr/bin/time ]; then
    # نحاول جمع إحصاءات عبر /usr/bin/time -v
    /usr/bin/time -v sh -lc "$cmd" >"$item_log" 2>&1 || true
    # استخراج مبسط
    cpu_peak=$(grep -i 'Maximum resident set size' "$item_log" | awk -F":" '{gsub(/ /,"",$2); print $2}' || echo "n/a")
    mem_peak="$cpu_peak"
  else
    sh -lc "$cmd" >"$item_log" 2>&1 || true
  fi

  end_ts_ms=$(date -u +%s)
  duration=$((end_ts_ms - start_ts_ms))

  # تحديد النجاح/الفشل عبر تحليل النص
  # ملاحظة: عبارة "No syntax errors detected" لا تُعدّ فشلًا للـ php -l
  status="passed"
  if [ "$item_name" = "php-lint" ]; then
    # تجنّب التطابق الخاطئ مع "No syntax errors detected" باشتراط حد بعد "syntax error"
    if grep -qiE "PHP Parse error:|PHP Fatal error:|Errors parsing|syntax error[^a-z]" "$item_log"; then
      status="failed"
    fi
  else
    if grep -qiE "(^|[^a-z])error([^a-z]|$)|fail|exception" "$item_log"; then
      status="failed"
    fi
  fi

  # إنشاء مقطع HTML ملون
  color="#2e7d32" # أخضر
  [ "$status" = "failed" ] && color="#c62828" # أحمر
  cat > "$html_snippet" <<HTML
<tr>
  <td style="padding:8px;">$item_name</td>
  <td style="padding:8px; color:$color; font-weight:bold;">$status</td>
  <td style="padding:8px;">$duration ثانية</td>
  <td style="padding:8px;">CPU(avg): $cpu_avg / peak: $cpu_peak</td>
  <td style="padding:8px;">MEM(avg): $mem_avg / peak: $mem_peak</td>
  <td style="padding:8px;"><a href="$(basename "$item_log")" target="_blank">سجل</a></td>
</tr>
HTML

  # JSON جزئي لعنصر
  cat > "$REPORTS_DIR/${item_name}.json" <<JSON
{
  "timestamp": "$(ts_iso)",
  "item": "$item_name",
  "status": "$status",
  "duration_seconds": $duration,
  "cpu_avg": "$cpu_avg",
  "cpu_peak": "$cpu_peak",
  "mem_avg": "$mem_avg",
  "mem_peak": "$mem_peak",
  "logfile": "$(basename "$item_log")"
}
JSON

  echo "$status"
}

generate_html_report() {
  out_html="$REPORTS_DIR/report_$(ts_iso).html"
  log_info "توليد تقرير HTML: $(basename "$out_html")"
  {
    echo "<html><head><meta charset=\"utf-8\"><title>تقرير التنفيذ المُحسّن</title>"
    echo "<style>body{font-family:Tahoma,Arial,sans-serif;background:#fafafa;color:#333} table{border-collapse:collapse;width:100%;background:#fff} th,td{border:1px solid #ddd} th{background:#f0f0f0}</style>"
    echo "</head><body>"
    echo "<h2>تقرير التنفيذ المُحسّن</h2>"
    echo "<p>الزمن: $(ts_iso)</p>"
    echo "<table><thead><tr><th>العنصر</th><th>الحالة</th><th>المدّة</th><th>المعالج</th><th>الذاكرة</th><th>السجل</th></tr></thead><tbody>"
    for s in "$REPORTS_DIR"/*_snippet.html; do [ -f "$s" ] && cat "$s"; done
    echo "</tbody></table>"
    echo "</body></html>"
  } > "$out_html"
}

generate_json_report() {
  out_json="$REPORTS_DIR/report_$(ts_iso).json"
  log_info "توليد تقرير JSON: $(basename "$out_json")"
  echo "[" > "$out_json"
  first=1
  for j in "$REPORTS_DIR"/*.json; do
    case "$j" in *report_*.json) continue ;; esac
    [ -f "$j" ] || continue
    if [ "$first" -eq 1 ]; then first=0; else echo "," >> "$out_json"; fi
    cat "$j" >> "$out_json"
  done
  echo "]" >> "$out_json"
}

# توليد قائمة المخالفين المختصرة
generate_offenders_list() {
  out_txt="$REPORTS_DIR/offenders_list.txt"
  ensure_dirs
  : > "$out_txt"
  echo "Offenders List — $(ts_iso)" >> "$out_txt"

  offenders=0

  # استخراج مخالفات php-lint إن وجدت
  if [ -f "$REPORTS_DIR/php-lint.json" ]; then
    lint_log=$(awk -F '"logfile":"' '{if (NF>1) {split($2,a,"\""); print a[1]; exit}}' "$REPORTS_DIR/php-lint.json")
    if [ -n "$lint_log" ] && [ -f "$REPORTS_DIR/$lint_log" ]; then
      awk '
        /PHP Parse error:|PHP Fatal error:|Errors parsing|syntax error[^a-z]/ {
          msg=$0; fp=""; ln="";
          if (match($0, / in ([^ ]+) on line ([0-9]+)/, m)) { fp=m[1]; ln=m[2]; }
          else if (match($0, /Errors parsing ([^ ]+)/, n)) { fp=n[1]; ln="n/a"; }
          gsub(/^\s+|\s+$/, "", msg);
          if (fp != "") { if (ln=="") ln="n/a"; print fp " : " ln " : " msg }
        }
      ' "$REPORTS_DIR/$lint_log" >> "$out_txt" || true
    fi
  fi

  # إن لم توجد مخالفات نضيف سطرًا واضحًا
  if [ "$(wc -l < "$out_txt")" -le 1 ]; then
    echo "No offenders" >> "$out_txt"
  fi

  log_info "تم توليد ملف المخالفين: $(basename "$out_txt")"
}

discover_items() {
  # إن وُجد ملف القائمة النهائية، نقرأه مباشرةً ونعتمد ترتيبه
  if [ -f "$REF_FILE" ]; then
    log_info "تحميل قائمة الأدوات من: $(basename \"$REF_FILE\")"
    items=""
    while IFS= read -r raw; do
      [ -z "$raw" ] && continue
      case "$raw" in \#*) continue ;; esac
      name=""; cmd=""
      if printf "%s" "$raw" | grep -q ":"; then
        name=$(printf "%s" "$raw" | sed -E 's/^([^:]+):.*$/\1/' | tr -d '\r')
        cmd=$(printf "%s" "$raw" | sed -E 's/^[^:]+:(.*)$/\1/' | tr -d '\r')
      else
        name=$(printf "%s" "$raw" | awk '{print $1}')
        cmd="$raw"
      fi
      [ -z "$name" ] && name=$(printf "%s" "$cmd" | awk '{print $1}')
      items=$(printf "%s\n%s:%s" "$items" "$name" "$cmd")
    done < "$REF_FILE"
    echo "$items" | sed '/^$/d'
    return 0
  fi
  # خلاف ذلك، نستخدم الاكتشاف الافتراضي
  items=""
  if [ -f "$WORKDIR/vendor/bin/phpunit" ]; then
    items="$items\nphpunit:$WORKDIR/vendor/bin/phpunit --testdox --colors=always"
  elif command -v phpunit >/dev/null 2>&1; then
    items="$items\nphpunit:phpunit --testdox --colors=always"
  fi
  if [ -f "$WORKDIR/vendor/bin/pest" ]; then
    items="$items\npest:$WORKDIR/vendor/bin/pest --colors"
  fi
  if command -v php >/dev/null 2>&1 && [ -f "$WORKDIR/artisan" ]; then
    items="$items\nartisan-test:php artisan test --parallel"
  fi
  if [ -f "$WORKDIR/composer.json" ]; then
    items="$items\ncomposer-validate:composer validate --strict"
  fi
  if command -v php >/dev/null 2>&1; then
    items="$items\nphp-lint:find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l"
  fi
  echo "$items" | sed '/^$/d'
}

# تقرير كامل للجولة الحالية باسم ثابت — موقّع قبل الاستدعاء
generate_full_run_reports() {
  out_html="$REPORTS_DIR/report_full_run.html"
  out_json="$REPORTS_DIR/report_full_run.json"
  global_msg="✅ All systems healthy"
  fail_count=0
  {
    echo "<html><head><meta charset=\"utf-8\"><title>Full Run Report</title>"
    echo "<style>body{font-family:Tahoma,Arial,sans-serif;background:#fafafa;color:#333} table{border-collapse:collapse;width:100%;background:#fff} th,td{border:1px solid #ddd} th{background:#f0f0f0}</style>"
    echo "</head><body>"
    echo "<h2>Full Run Report</h2>"
    echo "<p>Timestamp: $(ts_iso)</p>"
    echo "<table><thead><tr><th>العنصر</th><th>الحالة</th><th>المدّة</th><th>المعالج</th><th>الذاكرة</th><th>السجل</th></tr></thead><tbody>"
    if [ -f "$REPORTS_DIR/.last_run_items.txt" ]; then
      while IFS= read -r nm; do [ -z "$nm" ] && continue; s="$REPORTS_DIR/${nm}_snippet.html"; [ -f "$s" ] && cat "$s"; done < "$REPORTS_DIR/.last_run_items.txt"
    fi
    echo "</tbody></table>"
    echo "</body></html>"
  } > "$out_html"

  {
    echo "{"; echo "\"timestamp\":\"$(ts_iso)\",";
    echo "\"items\":["; first=1
    if [ -f "$REPORTS_DIR/.last_run_items.txt" ]; then
      while IFS= read -r nm; do
        [ -z "$nm" ] && continue
        jf="$REPORTS_DIR/${nm}.json"
        [ -f "$jf" ] || continue
        if grep -q '"status"\s*:\s*"failed"' "$jf" 2>/dev/null; then fail_count=$((fail_count+1)); fi
        if [ "$first" -eq 1 ]; then first=0; else echo ","; fi
        cat "$jf"
      done < "$REPORTS_DIR/.last_run_items.txt"
    fi
    echo "],"; [ "$fail_count" -gt 0 ] && global_msg="❌ Critical failures found"; echo "\"global_status\":\"$global_msg\"}"
  } > "$out_json"
}

run_all() {
  ensure_dirs
  send_notification "start" "بدء تنفيذ السكريبت المُحسّن"

  idx_start=-1
  if [ "${RESUME:-0}" = "1" ]; then
    idx_start=$(read_checkpoint_index)
    log_info "الاستئناف من مؤشر: $idx_start"
  fi

  items=$(discover_items)
  if [ -z "$items" ]; then
    log_warn "لم يتم العثور على عناصر للتنفيذ. سيتم الخروج."
    send_notification "empty" "لا توجد عناصر للتنفيذ"
    generate_html_report || true
    generate_json_report || true
    return 0
  fi

  # حفظ قائمة العناصر لهذه الجولة لإنشاء تقرير كامل لاحقًا
  : > "$REPORTS_DIR/.last_run_items.txt"
  total_items=$(printf "%s\n" "$items" | sed '/^$/d' | wc -l | awk '{print $1}')
  log_info "عدد العناصر المراد تنفيذها: $total_items"

  i=0
  echo "$items" | while IFS= read -r line; do
    name=$(printf "%s" "$line" | awk -F":" '{print $1}')
    # تنظيف المسافات لضمان مطابقة دقيقة لاسم العنصر
    name=$(printf "%s" "$name" | sed 's/^\s*//;s/\s*$//')
    cmd=$(printf "%s" "$line" | awk -F":" '{sub($1 ":","",$0); print $0}')
    echo "$name" >> "$REPORTS_DIR/.last_run_items.txt"

    # التحقق من وجود الأداة/الأمر وصلاحيته للتنفيذ
    exist_info=$(validate_executable "$name" "$cmd")
    existence=$(printf "%s" "$exist_info" | awk -F":" '{print $1}')
    cmd_real=$(printf "%s" "$exist_info" | awk -F":" '{sub($1 ":","",$0); print $0}')

    if [ "$idx_start" -ge 0 ] && [ "$i" -le "$idx_start" ]; then
      log_info "تخطي العنصر (استئناف): $name"
      i=$((i+1))
      continue
    fi

    # في حالة الأداة مفقودة نحاول الإصلاح أولًا
    if [ "$existence" = "MISSING" ]; then
      log_warn "العنصر مفقود/غير قابل للتنفيذ: $name — محاولة إصلاح تلقائي"
      check_global_limit_or_exit
      rc=$(attempt_auto_fix "$name" "$REPORTS_DIR/${name}_$(ts_iso).log")
      if [ "$rc" -eq 0 ]; then
        # إعادة التحقق بعد الإصلاح
        exist_info=$(validate_executable "$name" "$cmd")
        existence=$(printf "%s" "$exist_info" | awk -F":" '{print $1}')
        cmd_real=$(printf "%s" "$exist_info" | awk -F":" '{sub($1 ":","",$0); print $0}')
        log_info "تم إصلاح الاعتمادية — متابعة التنفيذ: $name"
      else
        log_warn "تعذر الإصلاح: $name — سيتم تسجيل النتيجة كفشل"
        # إنشاء سجل وملف JSON/HTML للعُنصر المفقود
        item_log="$REPORTS_DIR/${name}_$(ts_iso).log"
        echo "[MISSING] Executable/command not found: $cmd" > "$item_log"
        color="#c62828"
        cat > "$REPORTS_DIR/${name}_snippet.html" <<HTML
<tr>
  <td style="padding:8px;">$name</td>
  <td style="padding:8px; color:$color; font-weight:bold;">failed</td>
  <td style="padding:8px;">0 ثانية</td>
  <td style="padding:8px;">CPU(avg): n/a / peak: n/a</td>
  <td style="padding:8px;">MEM(avg): n/a / peak: n/a</td>
  <td style="padding:8px;"><a href="$(basename "$item_log")" target="_blank">سجل</a></td>
</tr>
HTML
        cat > "$REPORTS_DIR/${name}.json" <<JSON
{
  "timestamp": "$(ts_iso)",
  "item": "$name",
  "status": "failed",
  "duration_seconds": 0,
  "cpu_avg": "n/a",
  "cpu_peak": "n/a",
  "mem_avg": "n/a",
  "mem_peak": "n/a",
  "logfile": "$(basename "$item_log")"
}
JSON
        send_notification "auto-fix-failed" "تعذر الإصلاح: $name (مفقود)"
        update_checkpoint "$i" "$name"
        i=$((i+1))
        continue
      fi
    fi

    status=$(measure_and_run "$name" "$cmd_real")
    if [ "$status" = "failed" ]; then
      log_warn "العنصر فشل: $name — محاولة إصلاح تلقائي"
      check_global_limit_or_exit
      rc=$(attempt_auto_fix "$name" "$REPORTS_DIR/${name}_$(ts_iso).log")
      if [ "$rc" -eq 0 ]; then
        log_info "إصلاح ناجح — إعادة تشغيل العنصر: $name"
        status=$(measure_and_run "$name" "$cmd_real")
        if [ "$status" = "passed" ]; then
          send_notification "auto-fix-success" "تم إصلاح العنصر وإعادة تمريره: $name"
        else
          send_notification "auto-fix-retry-failed" "فشلت إعادة التشغيل بعد الإصلاح: $name"
        fi
      else
        send_notification "auto-fix-failed" "تعذر الإصلاح: $name"
      fi
    fi

    update_checkpoint "$i" "$name"
    i=$((i+1))
  done

  generate_html_report || true
  generate_json_report || true
  # توليد تقرير باسم ثابت يشمل نتائج هذه الجولة فقط
  generate_full_run_reports || true
  generate_offenders_list || true
  clear_checkpoint || true
  send_notification "done" "اكتمل التنفيذ المُحسّن"
}

usage() {
  cat <<USAGE
استخدام:
  $0            — تشغيل كامل من البداية
  RESUME=1 $0   — استئناف من نقطة التحقق إن وُجدت
USAGE
}

main() {
  cd "$WORKDIR"
  case "${1:-run}" in
    --help|-h) usage ;;
    *) run_all ;;
  esac
}

main "$@"

# تقرير كامل للجولة الحالية باسم ثابت
generate_full_run_reports() {
  out_html="$REPORTS_DIR/report_full_run.html"
  out_json="$REPORTS_DIR/report_full_run.json"
  # حالة عالمية مبسطة: فشل إن وُجد عنصر بحالة failed
  global_msg="✅ All systems healthy"
  fail_count=0
  # بناء HTML
  {
    echo "<html><head><meta charset=\"utf-8\"><title>Full Run Report</title>"
    echo "<style>body{font-family:Tahoma,Arial,sans-serif;background:#fafafa;color:#333} table{border-collapse:collapse;width:100%;background:#fff} th,td{border:1px solid #ddd} th{background:#f0f0f0}</style>"
    echo "</head><body>"
    echo "<h2>Full Run Report</h2>"
    echo "<p>Timestamp: $(ts_iso)</p>"
    echo "<table><thead><tr><th>العنصر</th><th>الحالة</th><th>المدّة</th><th>المعالج</th><th>الذاكرة</th><th>السجل</th></tr></thead><tbody>"
    if [ -f "$REPORTS_DIR/.last_run_items.txt" ]; then
      while IFS= read -r nm; do [ -z "$nm" ] && continue; s="$REPORTS_DIR/${nm}_snippet.html"; [ -f "$s" ] && cat "$s"; done < "$REPORTS_DIR/.last_run_items.txt"
    fi
    echo "</tbody></table>"
    echo "</body></html>"
  } > "$out_html"

  # بناء JSON
  {
    echo "{"; echo "\"timestamp\":\"$(ts_iso)\",";
    echo "\"items\":["; first=1
    if [ -f "$REPORTS_DIR/.last_run_items.txt" ]; then
      while IFS= read -r nm; do
        [ -z "$nm" ] && continue
        jf="$REPORTS_DIR/${nm}.json"
        [ -f "$jf" ] || continue
        # تتبع عدد الفشل
        if grep -q '"status"\s*:\s*"failed"' "$jf" 2>/dev/null; then fail_count=$((fail_count+1)); fi
        if [ "$first" -eq 1 ]; then first=0; else echo ","; fi
        cat "$jf"
      done < "$REPORTS_DIR/.last_run_items.txt"
    fi
    echo "],"; [ "$fail_count" -gt 0 ] && global_msg="❌ Critical failures found"; echo "\"global_status\":\"$global_msg\"}";
  } > "$out_json"
}
