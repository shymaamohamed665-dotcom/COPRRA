#!/usr/bin/env sh
# تشغيل التحقق الكامل لكل الأدوات والاختبارات وفقًا لملف مرجعي
# يُولّد تقارير HTML/JSON/TXT مع ملخص عالمي، ويدعم إصلاحًا تلقائيًا وتكرارًا حتى 3 مرات
# يدعم الاستئناف، الإشعارات (Slack/Mailpit)، الوضع المتوازي، ووضع المحاكاة

set -eu

WORKDIR="/var/www/html"
REPORTS_DIR="$WORKDIR/reports"
LOGFILE="$REPORTS_DIR/run_all_tests_automated_enhanced.log"
ERROR_LOG="$WORKDIR/storage/logs/laravel.log"
CHECKPOINT_FILE="$WORKDIR/.run_all_tests_checkpoint"
LOCK_DIR="$WORKDIR/.run_all_tests_lockdir"
MAILPIT_ENV="$WORKDIR/config/mailpit.env"
REF_FILE="$WORKDIR/القائمة النهائية للاختبارات والادوات.txt"
AUTO_HISTORY="$REPORTS_DIR/auto_run_history.log"

# أوضاع اختيارية
PARALLEL_MODE=${PARALLEL_MODE:-0}
DRY_RUN=${DRY_RUN:-0}
JOBS=${JOBS:-2}
CONTINUOUS_MODE=${CONTINUOUS_MODE:-0}

# حدود الإصلاح التلقائي العامة
GLOBAL_REPAIR_ATTEMPTS=0
GLOBAL_MAX_REPAIR_ATTEMPTS=${GLOBAL_MAX_REPAIR_ATTEMPTS:-100}
MAX_RETRIES_PER_ITEM=3

RUN_START_TS=$(date -u +%s)
GLOBAL_ITEM_COUNT=0
TOTAL_DURATION_SUM=0
GLOBAL_MAX_MEM_PEAK_KB=0
GLOBAL_ERRORS=0
GLOBAL_WARNINGS=0
GLOBAL_FAILURES=0
EXTRA_TOOLS_LIST=""
LAST_TEST_NAME=""; LAST_TEST_LOG=""; FIRST_ALERT_SENT=0

# أدوات مساعدة
mkdir -p "$REPORTS_DIR"
ts_iso() { date -u +%Y-%m-%dT%H:%M:%SZ; }
ts_file() { date -u +%Y-%m-%dT%H-%M-%SZ; }
log_info() { printf "[INFO %s] %s\n" "$(ts_iso)" "$*" | tee -a "$LOGFILE" >/dev/null; }
log_warn() { printf "[WARN %s] %s\n" "$(ts_iso)" "$*" | tee -a "$LOGFILE" >/dev/null; }
log_error() { printf "[ERROR %s] %s\n" "$(ts_iso)" "$*" | tee -a "$LOGFILE" >/dev/null; }

with_lock() {
  cmd="$1"
  if mkdir "$LOCK_DIR" 2>/dev/null; then
    trap 'rm -rf "$LOCK_DIR"' EXIT INT TERM
    sh -lc "$cmd"
    rm -rf "$LOCK_DIR" || true
    trap - EXIT INT TERM
  else
    sleep 1
    sh -lc "$cmd"
  fi
}

clear_checkpoint() { rm -f "$CHECKPOINT_FILE" || true; }

safe_exit() {
  code=${1:-1}; msg=${2:-"خروج آمن"}
  log_error "سيتم الإنهاء: $msg (code=$code)"
  snap="$REPORTS_DIR/crash_snapshot_$(ts_iso).json"
  printf '{"timestamp":"%s","last_test":"%s","reason":"%s","log_path":"%s","repair_attempts":%s,"checkpoint":"%s"}\n' \
    "$(ts_iso)" "$LAST_TEST_NAME" "$msg" "$LAST_TEST_LOG" "$GLOBAL_REPAIR_ATTEMPTS" "$CHECKPOINT_FILE" > "$snap" || true
  log_warn "تم توليد لقطة تعطل: $(basename "$snap")"
  if [ -f "$ERROR_LOG" ]; then
    log_error "عرض آخر 200 سطر من سجل الأخطاء: $ERROR_LOG"
    tail -n 200 "$ERROR_LOG" | sed 's/^/[ERR] /' | tee -a "$LOGFILE" >/dev/null || true
  fi
  exit "$code"
}

load_mailpit_config() { [ -f "$MAILPIT_ENV" ] && . "$MAILPIT_ENV" || true; }

send_notification() {
  event=${1:-unknown}; detail=${2:-""}
  payload=$(printf '{"timestamp":"%s","event":"%s","detail":"%s"}' "$(ts_iso)" "$event" "$detail")
  # Slack
  if command -v curl >/dev/null 2>&1 && [ -n "${SLACK_WEBHOOK_URL:-}" ]; then
    curl -fsS -X POST -H 'Content-Type: application/json' -d "$payload" "$SLACK_WEBHOOK_URL" >/dev/null 2>&1 || true
    log_info "أُرسل إشعار Slack: $event"
  fi
  # Mailpit/SMTP
  load_mailpit_config
  if [ "${MAIL_MODE:-}" != "" ] && command -v curl >/dev/null 2>&1; then
    subj="COPRRA: $event"; ts="$(ts_iso)"; tmp="$REPORTS_DIR/mail_${event}_$(ts_iso).eml"
    { echo "From: ${MAIL_FROM:-robot@coprra.local}"; echo "To: ${MAIL_TO:-dev@coprra.local}"; echo "Subject: $subj"; echo "Date: $ts"; echo "Content-Type: text/plain; charset=utf-8"; echo; echo "الحدث: $event"; echo "الزمن: $ts"; echo "التفاصيل: $detail"; } > "$tmp"
    smtp_url="smtp://${SMTP_HOST:-mailpit}:${SMTP_PORT:-1025}"
    if [ -n "${SMTP_USER:-}" ]; then
      curl -fsS --url "$smtp_url" --mail-from "${MAIL_FROM:-robot@coprra.local}" --mail-rcpt "${MAIL_TO:-dev@coprra.local}" --upload-file "$tmp" --user "${SMTP_USER}:${SMTP_PASS:-}" >/dev/null 2>&1 || true
    else
      curl -fsS --url "$smtp_url" --mail-from "${MAIL_FROM:-robot@coprra.local}" --mail-rcpt "${MAIL_TO:-dev@coprra.local}" --upload-file "$tmp" >/dev/null 2>&1 || true
    fi
    log_info "أُرسل إشعار بريد: $event"
  fi
  if [ -z "${SLACK_WEBHOOK_URL:-}" ] && [ -z "${MAIL_MODE:-}" ]; then log_info "إشعار: $event — التفاصيل: $detail"; fi
}

update_checkpoint() {
  idx=$1; name=$2; status=${3:-unknown}
  printf '{"timestamp":"%s","last_completed_index":%s,"last_completed_name":"%s","last_status":"%s"}\n' "$(ts_iso)" "$idx" "$name" "$status" > "$CHECKPOINT_FILE"
}

read_checkpoint_index() {
  [ -f "$CHECKPOINT_FILE" ] && grep -o '"last_completed_index"\s*:\s*[0-9]\+' "$CHECKPOINT_FILE" | awk -F":" '{gsub(/ /,""); print $2}' 2>/dev/null || echo -1
}

check_global_limit_or_exit() {
  if [ "$GLOBAL_REPAIR_ATTEMPTS" -ge "$GLOBAL_MAX_REPAIR_ATTEMPTS" ]; then
    safe_exit 2 "تجاوز حد محاولات الإصلاح التلقائي ($GLOBAL_MAX_REPAIR_ATTEMPTS)"
  fi
}

local_repair_attempt() { GLOBAL_REPAIR_ATTEMPTS=$((GLOBAL_REPAIR_ATTEMPTS+1)); log_warn "محاولة إصلاح تلقائي رقم $GLOBAL_REPAIR_ATTEMPTS من $GLOBAL_MAX_REPAIR_ATTEMPTS"; }

smart_local_repair_fallback() {
  item="$1"; item_log="$2"; fb="$REPORTS_DIR/fallback_${item}_$(ts_file).log"
  log_warn "بدء إصلاح محلي لعُنصر: $item"
  {
    echo "["$(ts_iso)"] إصلاح صلاحيات Laravel"; chmod -R 775 "$WORKDIR/storage" "$WORKDIR/bootstrap/cache" 2>&1 || true; chown -R www-data:www-data "$WORKDIR/storage" "$WORKDIR/bootstrap/cache" 2>&1 || true
    echo "["$(ts_iso)"] إعادة تثبيت تبعيات Composer"; if command -v composer >/dev/null 2>&1; then composer install --no-interaction --no-progress --prefer-dist 2>&1 || true; elif [ -f "$WORKDIR/composer.phar" ]; then php "$WORKDIR/composer.phar" install --no-interaction --no-progress --prefer-dist 2>&1 || true; fi
    echo "["$(ts_iso)"] تنظيف كاش Laravel"; if command -v php >/dev/null 2>&1 && [ -f "$WORKDIR/artisan" ]; then php artisan optimize:clear 2>&1 || true; php artisan config:clear 2>&1 || true; php artisan route:clear 2>&1 || true; php artisan view:clear 2>&1 || true; php artisan cache:clear 2>&1 || true; php artisan config:cache 2>&1 || true; fi
  } > "$fb"; log_info "سجل الإصلاح المحلي: $(basename "$fb")"; echo 0
}

attempt_auto_fix() {
  item="$1"; item_log="$2"; local_repair_attempt; fix_json="$REPORTS_DIR/auto_fix_${item}_$(ts_file).json"; tmp_out="$REPORTS_DIR/auto_fix_${item}_out.txt"; rc=1; ran_tool=0
  if command -v python3 >/dev/null 2>&1 && [ -f "$WORKDIR/ai_tools/python_auto_fixer.py" ]; then ran_tool=1; log_info "استدعاء مُصلّح Python لعُنصر: $item"; python3 "$WORKDIR/ai_tools/python_auto_fixer.py" --item "$item" --log "$item_log" --out "$fix_json" >/dev/null 2>"$tmp_out" || true; fi
  if command -v php >/dev/null 2>&1 && [ -f "$WORKDIR/ai_tools/php_auto_fixer.php" ]; then ran_tool=1; log_info "استدعاء مُصلّح PHP لعُنصر: $item"; php "$WORKDIR/ai_tools/php_auto_fixer.php" "$item" "$item_log" "$fix_json" >/dev/null 2>>"$tmp_out" || true; fi
  if [ -f "$fix_json" ] && grep -q '"success"\s*:\s*true' "$fix_json" 2>/dev/null; then rc=0; log_info "نجح الإصلاح التلقائي للعُنصر: $item"; elif [ "$ran_tool" -eq 0 ]; then log_warn "لا توجد أدوات إصلاح — تفعيل إصلاح محلي"; rc=$(smart_local_repair_fallback "$item" "$item_log"); else rc=1; log_warn "لم ينجح الإصلاح التلقائي للعُنصر: $item"; fi; echo "$rc"
}

resolve_composer_cmd() {
  cmd="$*"; if command -v composer >/dev/null 2>&1; then echo "$cmd"; elif [ -f "$WORKDIR/composer.phar" ]; then echo "php $WORKDIR/composer.phar ${cmd#composer }"; else echo "$cmd"; fi
}

validate_executable() {
  name="$1"; cmd="$2"; status="FOUND"; exists=0
  first_word=$(printf "%s" "$cmd" | awk '{print $1}')
  case "$cmd" in
    composer*) if command -v composer >/dev/null 2>&1 || [ -f "$WORKDIR/composer.phar" ]; then exists=1; cmd=$(resolve_composer_cmd "$cmd"); fi ;;
    php*) if command -v php >/dev/null 2>&1; then exists=1; fi ;;
    node*|npm*) if command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1; then exists=1; fi ;;
    *) if command -v "$first_word" >/dev/null 2>&1; then exists=1; fi ;;
  esac
  if [ "$exists" -eq 0 ]; then status="MISSING"; fi
  printf "%s:%s\n" "$status" "$cmd"
}

parse_counts() {
  f="$1"; ec=0; wc=0; fc=0
  ec=$(grep -Ei "error|exception|fatal" "$f" | wc -l | awk '{print $1}') || ec=0
  wc=$(grep -Ei "warning|deprecated" "$f" | wc -l | awk '{print $1}') || wc=0
  fc=$(grep -Ei "fail(ed)?|assertion.*failed|ERRORS" "$f" | wc -l | awk '{print $1}') || fc=0
  printf "%s %s %s\n" "$ec" "$wc" "$fc"
}

measure_and_run() {
  item="$1"; cmd="$2"; item_log="$REPORTS_DIR/${item}_$(ts_file).log"; start=$(date -u +%s); mem_peak_kb=0
  LAST_TEST_NAME="$item"; LAST_TEST_LOG="$item_log"
  log_info "بدء تنفيذ: $item — الأمر: $cmd"
  if [ "$DRY_RUN" = "1" ]; then echo "[DRY-RUN] تم التخطي: $cmd" > "$item_log"; else
    if [ -x /usr/bin/time ]; then /usr/bin/time -v sh -lc "$cmd" > "$item_log" 2>&1 || true; mem_peak_kb=$(grep -i 'Maximum resident set size' "$item_log" | awk -F":" '{gsub(/[^0-9]/,""); print $2}' 2>/dev/null || echo 0)
    else sh -lc "$cmd" > "$item_log" 2>&1 || true; fi
  fi
  dur=$(($(date -u +%s) - start))
  GLOBAL_ITEM_COUNT=$((GLOBAL_ITEM_COUNT+1)); TOTAL_DURATION_SUM=$((TOTAL_DURATION_SUM + dur)); [ "$mem_peak_kb" -gt "$GLOBAL_MAX_MEM_PEAK_KB" ] && GLOBAL_MAX_MEM_PEAK_KB="$mem_peak_kb"
  read ec wc fc <<EOF
$(parse_counts "$item_log")
EOF
  [ "$ec" -gt 0 ] && GLOBAL_ERRORS=$((GLOBAL_ERRORS + ec))
  [ "$wc" -gt 0 ] && GLOBAL_WARNINGS=$((GLOBAL_WARNINGS + wc))
  [ "$fc" -gt 0 ] && GLOBAL_FAILURES=$((GLOBAL_FAILURES + fc))
  status="passed"; [ "$DRY_RUN" = "1" ] && status="skipped"; [ "$ec" -gt 0 -o "$fc" -gt 0 ] && status="failed"; printf "%s:%s:%s:%s:%s\n" "$status" "$dur" "$mem_peak_kb" "$ec" "$wc"
}

retry_with_repair() {
  item="$1"; cmd="$2"; attempts=0; final_status="passed"; last_log="$REPORTS_DIR/${item}_$(ts_file).log"; repair_done=0
  while :; do
    IFS=: read -r s dur mem ec wc <<EOF
$(measure_and_run "$item" "$cmd")
EOF
    final_status="$s"; last_log=$(ls -1t "$REPORTS_DIR/${item}_"*".log" 2>/dev/null | head -n1 || echo "$last_log")
    if [ "$s" = "failed" ]; then
      if [ "$FIRST_ALERT_SENT" -eq 0 ]; then FIRST_ALERT_SENT=1; send_notification "first-error" "أول خطأ/أداة مفقودة عند: $item"; fi
      attempts=$((attempts+1)); [ "$attempts" -gt "$MAX_RETRIES_PER_ITEM" ] && break
      check_global_limit_or_exit
      rc=$(attempt_auto_fix "$item" "$last_log")
      if [ "$rc" -eq 0 ]; then repair_done=1; log_info "إصلاح ناجح — إعادة تجربة ($attempts): $item"; else log_warn "تعذر الإصلاح — إعادة تجربة ($attempts): $item"; fi
      continue
    fi
    break
  done
  printf "%s:%s:%s:%s:%s:%s\n" "$final_status" "$attempts" "$last_log" "$dur" "$mem" "$wc"
}

load_reference_items() {
  items=""; ref_names=""
  if [ -f "$REF_FILE" ]; then
    while IFS= read -r line; do
      [ -z "${line##\#*}" ] && continue
      [ -z "${line}" ] && continue
      name=""; cmd=""
      if printf "%s" "$line" | grep -q ":"; then
        name=$(printf "%s" "$line" | sed -E 's/^([^:]+):.*$/\1/' | tr -d '\r')
        cmd=$(printf "%s" "$line" | sed -E 's/^[^:]+:(.*)$/\1/' | tr -d '\r')
      else
        name=$(printf "%s" "$line" | awk '{print $1}')
        cmd="$line"
      fi
      [ -z "$name" ] && name=$(printf "%s" "$cmd" | awk '{print $1}')
      items=$(printf "%s\n%s:%s" "$items" "$name" "$cmd")
      ref_names=$(printf "%s\n%s" "$ref_names" "$name")
    done < "$REF_FILE"
  else
    log_warn "الملف المرجعي غير موجود: $(basename "$REF_FILE") — سيتم استخدام قائمة افتراضية"
    items=$(printf "%s\n%s\n%s" \
      "composer-validate:composer validate --no-interaction --no-progress" \
      "php-lint:find . -type f -name '*.php' -not -path './vendor/*' -exec php -l {} +" \
      "artisan-test:php artisan test --no-interaction")
    ref_names=$(printf "%s\n%s\n%s" "composer-validate" "php-lint" "artisan-test")
  fi
  printf "%s\n---REFNAMES---\n%s\n" "$items" "$ref_names"
}

scan_extra_tools() {
  # يفحص أدوات شائعة ويضيفها كأدوات إضافية إن لم تكن ضمن المرجع
  ref_names_set="$1"; extras=""
  add_if_present() { tool="$1"; path="$2"; [ -e "$path" ] || return 0; echo "$ref_names_set" | grep -qx "$tool" || extras=$(printf "%s\n%s" "$extras" "$tool"); }
  add_if_present "phpunit" "$WORKDIR/phpunit.xml"; add_if_present "phpunit.phar" "$WORKDIR/phpunit.phar"
  add_if_present "phpstan" "$WORKDIR/phpstan.neon"; add_if_present "psalm" "$WORKDIR/psalm.xml"
  add_if_present "pint" "$WORKDIR/pint.json"; add_if_present "rector" "$WORKDIR/rector.php"
  add_if_present "deptrac" "$WORKDIR/deptrac.yaml"; add_if_present "phpmd" "$WORKDIR/phpmd.xml"
  add_if_present "phpmetrics" "$WORKDIR/phpmetrics.json"
  EXTRA_TOOLS_LIST="$extras"
}

append_item_reports() {
  name="$1"; status="$2"; existence="$3"; attempts="$4"; dur="$5"; mem="$6"; ec="$7"; wc="$8"; logf="$9"
  # HTML snippet
  color="#2e7d32"; [ "$status" = "failed" ] && color="#c62828"; [ "$status" = "skipped" ] && color="#6d4c41"; [ "$status" = "fixed" ] && color="#1565c0"
  cat >> "$REPORTS_DIR/report_full_audit_items.html" <<HTML
<tr>
  <td style="padding:8px;">$name</td>
  <td style="padding:8px;">$existence</td>
  <td style="padding:8px; color:$color; font-weight:bold;">$status</td>
  <td style="padding:8px;">Errors: $ec, Warnings: $wc</td>
  <td style="padding:8px;">Retries: $attempts</td>
  <td style="padding:8px;">Duration: $dur s</td>
  <td style="padding:8px;">Mem peak: ${mem:-0} KB</td>
  <td style="padding:8px;"><a href="$(basename "$logf")" target="_blank">Log</a></td>
</tr>
HTML
  # JSON line
  printf '{"name":"%s","existence":"%s","status":"%s","errors":%s,"warnings":%s,"retries":%s,"duration_seconds":%s,"mem_peak_kb":%s,"log":"%s"}\n' \
    "$name" "$existence" "$status" "$ec" "$wc" "$attempts" "$dur" "${mem:-0}" "$(basename "$logf")" >> "$REPORTS_DIR/report_full_audit_items.jsonl"
  # TXT line
  printf "%-20s | %-7s | %-7s | E:%-3s W:%-3s | Retries:%-2s | T:%-4ss | Mem:%-8s | Log:%s\n" \
    "$name" "$existence" "$status" "$ec" "$wc" "$attempts" "$dur" "${mem:-0}KB" "$(basename "$logf")" >> "$REPORTS_DIR/report_full_audit_items.txt"
}

process_item() {
  name="$1"; cmd="$2"; exist_info=$(validate_executable "$name" "$cmd"); existence=$(printf "%s" "$exist_info" | awk -F":" '{print $1}') ; cmd_real=$(printf "%s" "$exist_info" | awk -F":" '{sub($1 ":","",$0); print $0}')
  status="passed"; attempts=0; dur=0; mem=0; ec=0; wc=0; logf="$REPORTS_DIR/${name}_$(ts_file).log"
  if [ "$existence" = "MISSING" ]; then
    status="missing"; check_global_limit_or_exit; rc=$(attempt_auto_fix "$name" "$logf")
    if [ "$rc" -eq 0 ]; then existence="FIXED"; status="fixed"; else status="failed"; fi
    IFS=: read -r s a last dur mem wc <<EOF
$(retry_with_repair "$name" "$cmd_real")
EOF
    # parse last run counts
    logf="$last"; attempts="$a"; status="$s"; # wc from function holds warnings; errors added globally
    read ec wc _ <<EOF
$(parse_counts "$logf")
EOF
    append_item_reports "$name" "$status" "$existence" "$attempts" "$dur" "$mem" "$ec" "$wc" "$logf"; update_checkpoint 0 "$name" "$status"; return 0
  fi
  IFS=: read -r s a last dur mem wc <<EOF
$(retry_with_repair "$name" "$cmd_real")
EOF
  logf="$last"; attempts="$a"; status="$s"; read ec wc _ <<EOF
$(parse_counts "$logf")
EOF
  append_item_reports "$name" "$status" "$existence" "$attempts" "$dur" "$mem" "$ec" "$wc" "$logf"; update_checkpoint 0 "$name" "$status"
}

generate_final_reports() {
  # HTML
  out_html="$REPORTS_DIR/report_full_audit.html"; out_json="$REPORTS_DIR/report_full_audit.json"; out_txt="$REPORTS_DIR/report_full_audit.txt"
  avg_time=0; [ "$GLOBAL_ITEM_COUNT" -gt 0 ] && avg_time=$((TOTAL_DURATION_SUM / GLOBAL_ITEM_COUNT))
  total_runtime=$(($(date -u +%s) - RUN_START_TS))
  # recompute totals from items to ensure accurate global summary
  tot_errors=0; tot_warnings=0; tot_fail_items=0
  if [ -f "$REPORTS_DIR/report_full_audit_items.jsonl" ]; then
    tot_errors=$(grep -o '"errors"\s*:\s*[0-9]\+' "$REPORTS_DIR/report_full_audit_items.jsonl" | awk -F":" '{sum += $2} END {print sum+0}')
    tot_warnings=$(grep -o '"warnings"\s*:\s*[0-9]\+' "$REPORTS_DIR/report_full_audit_items.jsonl" | awk -F":" '{sum += $2} END {print sum+0}')
    tot_fail_items=$(grep -c '"status"\s*:\s*"failed"' "$REPORTS_DIR/report_full_audit_items.jsonl" | awk '{print $1}')
  fi
  GLOBAL_ERRORS="$tot_errors"; GLOBAL_WARNINGS="$tot_warnings"; GLOBAL_FAILURES="$tot_fail_items"
  # global status
  global_msg="✅ All systems healthy"
  if [ "$GLOBAL_FAILURES" -gt 0 ]; then
    global_msg="❌ Critical failures found"
  elif [ "$GLOBAL_ERRORS" -gt 0 ]; then
    global_msg="❌ Critical failures found"
  elif [ "$GLOBAL_WARNINGS" -gt 0 ]; then
    global_msg="⚠️ Some tools have warnings"
  fi
  {
    echo "<html><head><meta charset=\"utf-8\"><title>Full Audit Report</title>"
    echo "<style>body{font-family:Tahoma,Arial,sans-serif;background:#fafafa;color:#333} table{border-collapse:collapse;width:100%;background:#fff} th,td{border:1px solid #ddd} th{background:#f0f0f0}</style>"
    echo "</head><body>"
    echo "<h2>Full Audit Report</h2>"
    echo "<p>Timestamp: $(ts_iso)</p>"
    echo "<p><strong>Global Status:</strong> $global_msg</p>"
    echo "<h3>Items</h3>"
    echo "<table><thead><tr><th>Tool</th><th>Existence</th><th>Status</th><th>Errors/Warnings</th><th>Retries</th><th>Duration</th><th>Mem Peak</th><th>Log</th></tr></thead><tbody>"
    [ -f "$REPORTS_DIR/report_full_audit_items.html" ] && cat "$REPORTS_DIR/report_full_audit_items.html"
    echo "</tbody></table>"
    echo "<h3>Extra tools detected</h3><ul>"
    if [ -n "$EXTRA_TOOLS_LIST" ]; then echo "$EXTRA_TOOLS_LIST" | while IFS= read -r e; do [ -n "$e" ] && echo "<li>$e</li>"; done; else echo "<li>None</li>"; fi
    echo "</ul>"
    echo "<h3>Performance Summary</h3><ul>"
    echo "<li>Total runtime: $total_runtime s</li>"
    echo "<li>Average test time: $avg_time s</li>"
    echo "<li>Max memory peak: ${GLOBAL_MAX_MEM_PEAK_KB} KB</li>"
    echo "<li>Total repair attempts: $GLOBAL_REPAIR_ATTEMPTS</li>"
    echo "<li>Total errors: $GLOBAL_ERRORS, warnings: $GLOBAL_WARNINGS, failures: $GLOBAL_FAILURES</li>"
    echo "</ul>"
    echo "</body></html>"
  } > "$out_html"
  # JSON
  {
    echo "{"; echo "\"timestamp\":\"$(ts_iso)\","
    echo "\"global_status\":\"$global_msg\",";
    echo "\"performance\":{\"total_runtime_seconds\":$total_runtime,\"average_test_time_seconds\":$avg_time,\"max_memory_peak_kb\":$GLOBAL_MAX_MEM_PEAK_KB,\"total_repair_attempts\":$GLOBAL_REPAIR_ATTEMPTS,\"total_errors\":$GLOBAL_ERRORS,\"total_warnings\":$GLOBAL_WARNINGS,\"total_failures\":$GLOBAL_FAILURES},"
    echo "\"items\":["; first=1; if [ -f "$REPORTS_DIR/report_full_audit_items.jsonl" ]; then while IFS= read -r jl; do [ -z "$jl" ] && continue; if [ "$first" -eq 1 ]; then first=0; else echo ","; fi; printf "%s" "$jl"; done < "$REPORTS_DIR/report_full_audit_items.jsonl"; fi
    echo "],\"extras\":["; first=1; if [ -n "$EXTRA_TOOLS_LIST" ]; then echo "$EXTRA_TOOLS_LIST" | while IFS= read -r e; do [ -z "$e" ] && continue; if [ "$first" -eq 1 ]; then first=0; else echo ","; fi; printf '"%s"' "$e"; done; fi; echo "]}"
  } > "$out_json"
  # TXT
  {
    echo "Full Audit Report"; echo "Timestamp: $(ts_iso)"; echo "Global: $global_msg"; echo
    echo "Items:"; [ -f "$REPORTS_DIR/report_full_audit_items.txt" ] && cat "$REPORTS_DIR/report_full_audit_items.txt" || echo "(no items)"; echo
    echo "Extra tools detected:"; if [ -n "$EXTRA_TOOLS_LIST" ]; then echo "$EXTRA_TOOLS_LIST"; else echo "None"; fi; echo
    echo "Performance:"; echo "- Total runtime: $total_runtime s"; echo "- Average time: $avg_time s"; echo "- Max mem peak: ${GLOBAL_MAX_MEM_PEAK_KB} KB"; echo "- Total repair attempts: $GLOBAL_REPAIR_ATTEMPTS"; echo "- Errors: $GLOBAL_ERRORS, warnings: $GLOBAL_WARNINGS, failures: $GLOBAL_FAILURES"
  } > "$out_txt"
}

run_all_once() {
  send_notification "start" "بدء التدقيق الكامل"
  # تحميل العناصر المرجعية وأسماءها
  ref_out=$(load_reference_items)
  items=$(printf "%s" "$ref_out" | awk 'BEGIN{p=1} p==1 {if($0=="---REFNAMES---") {p=0; next} print}' )
  ref_names=$(printf "%s" "$ref_out" | awk 'BEGIN{p=0} p==0 {if($0=="---REFNAMES---") {p=1; next} else if(p==1) print}' )
  scan_extra_tools "$ref_names"
  # مسح ملفات عناصر التقارير المجمّعة السابقة
  : > "$REPORTS_DIR/report_full_audit_items.html"; : > "$REPORTS_DIR/report_full_audit_items.jsonl"; : > "$REPORTS_DIR/report_full_audit_items.txt"
  # تنفيذ العناصر
  if [ "$PARALLEL_MODE" = "1" ]; then
    idx=0; pids=""; names=""; cmds=""
    echo "$items" | while IFS= read -r line; do [ -z "$line" ] && continue; name=$(printf "%s" "$line" | sed -E 's/^([^:]+):.*$/\1/'); cmd=$(printf "%s" "$line" | sed -E 's/^[^:]+:(.*)$/\1/'); ( process_item "$name" "$cmd" ) & pids="$pids $!"; idx=$((idx+1)); done
    for pid in $pids; do wait "$pid" || true; done
  else
    echo "$items" | while IFS= read -r line; do [ -z "$line" ] && continue; name=$(printf "%s" "$line" | sed -E 's/^([^:]+):.*$/\1/'); cmd=$(printf "%s" "$line" | sed -E 's/^[^:]+:(.*)$/\1/'); process_item "$name" "$cmd"; done
  fi
  generate_final_reports || true
  clear_checkpoint || true
  if [ "$GLOBAL_ERRORS" -gt 0 -o "$GLOBAL_FAILURES" -gt 0 ]; then
    send_notification "audit-done-failures" "انتهى التدقيق مع فشل/أخطاء"; if [ -f "$ERROR_LOG" ]; then tail -n 200 "$ERROR_LOG" | tee -a "$LOGFILE" >/dev/null || true; fi
  else
    send_notification "audit-done-success" "انتهى التدقيق بنجاح"; fi
}

run_all() {
  RUN_START_TS=$(date -u +%s)
  run_all_once
  if [ "$CONTINUOUS_MODE" = "1" ]; then
    log_info "تفعيل وضع التحقق المستمر كل 5 دقائق"
    while :; do echo "$(ts_iso) — إعادة تشغيل التدقيق الكامل" >> "$AUTO_HISTORY"; sleep 300; RUN_START_TS=$(date -u +%s); run_all_once || true; done
  fi
}

usage() {
  cat <<USAGE
استخدام:
  $0               — تشغيل التدقيق الكامل مرة واحدة
  RESUME=1 $0      — الاستئناف من نقطة تحقق إن وجدت (يُحدّث بعد كل عنصر)
  $0 --dry-run     — محاكاة كاملة دون تنفيذ الأوامر
  $0 --parallel    — تشغيل متوازي ثم إصلاح الفاشل
  CONTINUOUS_MODE=1 $0 — وضع التحقق المستمر كل 5 دقائق
USAGE
}

main() {
  cd "$WORKDIR"
  case "${1:-run}" in
    --help|-h) usage ;;
    --dry-run) DRY_RUN=1 ; run_all ;;
    --parallel) PARALLEL_MODE=1 ; run_all ;;
    *) run_all ;;
  esac
}

main "$@"