#!/usr/bin/env sh
set -eu
RDIR="/var/www/html/reports"
JSON_FULL="$RDIR/report_full_audit.json"
ITEMS_TXT="$RDIR/report_full_audit_items.txt"
OUT="$RDIR/execution_summary.txt"

ts() { date -u +%Y-%m-%dT%H:%M:%SZ; }

mkdir -p "$RDIR"
GLOBAL_STATUS="(unknown)"
TOTAL_ERRORS=0
TOTAL_WARNINGS=0
TOTAL_FAILURES=0
SOURCE_JSON=""

# نُفضّل دائمًا أحدث تقرير مُجمّع من السكربت الرئيسي، وإن لم يوجد نعود للتقرير الكامل
LATEST=$(ls -t "$RDIR"/report_*.json 2>/dev/null | head -n1 || true)
if [ -n "$LATEST" ] && [ -f "$LATEST" ]; then
  SOURCE_JSON="$LATEST"
  TOTAL_FAILURES=$(grep -o '"status":"failed"' "$SOURCE_JSON" | wc -l | tr -cd '0-9') || TOTAL_FAILURES=0
  TOTAL_ERRORS=$TOTAL_FAILURES
  TOTAL_WARNINGS=0
  if [ "$TOTAL_FAILURES" -gt 0 ]; then
    GLOBAL_STATUS="❌ Critical failures found"
  else
    GLOBAL_STATUS="✅ All systems healthy"
  fi
elif [ -f "$JSON_FULL" ]; then
  SOURCE_JSON="$JSON_FULL"
  GLOBAL_STATUS=$(awk -F '"global_status":"' '{if (NF>1) {split($2, a, "\""); print a[1]; exit}}' "$JSON_FULL") || GLOBAL_STATUS="(parse-error)"
  TOTAL_ERRORS=$(awk -F '"total_errors":' '{if (NF>1) {split($2, a, ","); print a[1]; exit}}' "$JSON_FULL" | tr -cd '0-9') || TOTAL_ERRORS=0
  TOTAL_WARNINGS=$(awk -F '"total_warnings":' '{if (NF>1) {split($2, a, ","); print a[1]; exit}}' "$JSON_FULL" | tr -cd '0-9') || TOTAL_WARNINGS=0
  TOTAL_FAILURES=$(awk -F '"total_failures":' '{if (NF>1) {split($2, a, ","); print a[1]; exit}}' "$JSON_FULL" | tr -cd '0-9') || TOTAL_FAILURES=0
fi

{
  echo "Execution Summary"
  echo "Timestamp: $(ts)"
  echo "Global Status: $GLOBAL_STATUS"
  echo "Totals: errors=$TOTAL_ERRORS, warnings=$TOTAL_WARNINGS, failed_items=$TOTAL_FAILURES"
  echo
  echo "Items:"
  if [ -f "$ITEMS_TXT" ] && [ -n "$SOURCE_JSON" ] && [ -f "$SOURCE_JSON" ] && [ "$ITEMS_TXT" -nt "$SOURCE_JSON" ]; then
    sed -n '1,120p' "$ITEMS_TXT"
  elif [ -n "$SOURCE_JSON" ] && [ -f "$SOURCE_JSON" ]; then
    # استخراج عناصر مبسطة من تقرير JSON الحديث بشكل موثوق دون الاعتماد على ترتيب الحقول
    awk '
      BEGIN { it=""; st=""; lf="" }
      /\"item\"/     { split($0,a,"\""); if (length(a)>=4) it=a[4] }
      /\"status\"/   { split($0,a,"\""); if (length(a)>=4) st=a[4] }
      /\"logfile\"/  { split($0,a,"\""); if (length(a)>=4) lf=a[4] }
      /},?$/ {
        if (it != "") {
          printf "%s    | FOUND   | %s  | E:%d   W:%d   | Log:%s\n", it, st, (st=="failed"?1:0), 0, lf;
        }
        it=""; st=""; lf="";
      }
    ' "$SOURCE_JSON"
  else
    echo "(items file not found)"
  fi
  echo "Done."
} > "$OUT"

echo "$OUT"