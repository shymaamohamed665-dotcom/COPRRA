#!/usr/bin/env bash
set -Eeuo pipefail

REPO="/mnt/c/Users/Gaser/Desktop/COPRRA"
INPUT="${INPUT:-$REPO/قائمة_الاختبارات_والأدوات.txt}"
FIXED="${FIXED:-$REPO/قائمة_الاختبارات_والأدوات التي تم اصلاحها.txt}"
FAILED="${FAILED:-$REPO/قائمة_العناصر_المستعصية.txt}"
REPORT_DIR="${REPORT_DIR:-/var/www/html/reports}"
MASTER_LOG="$REPORT_DIR/full_auto_run.log"

log() {
  local ts
  ts="$(date -Iseconds)"
  printf "[%s] %s\n" "$ts" "$*" | tee -a "$MASTER_LOG" >/dev/null
}

ensure_env() {
  mkdir -p "$REPORT_DIR"
  touch "$MASTER_LOG"
}

sanitize() {
  local input="$1"
  # Avoid tr range warnings by placing '-' at start of class
  echo "$input" | LC_ALL=C tr -cd '[:alnum:]-._/ ' | tr ' ' '_'
}

is_action_item() {
  local item="$1"
  local trimmed
  trimmed="$(echo "$item" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
  # Skip empty or comment lines
  [[ -z "$trimmed" ]] && return 1
  [[ "$trimmed" =~ ^[[:space:]]*# ]] && return 1
  # Skip pure separators (box drawing, dashes, equals)
  [[ "$trimmed" =~ ^[═─━=]+$ ]] && return 1
  # Actionable if it points to an existing path in the repo
  if [ -e "$REPO/$trimmed" ]; then
    return 0
  fi
  # Actionable if it targets known test suites or test files
  if [[ "$trimmed" =~ ^tests/(Unit|Feature|Browser)(/.*)?$ ]]; then
    return 0
  fi
  if [[ "$trimmed" =~ ^tests/.*\.php$ ]]; then
    return 0
  fi
  return 1
}

backup_item() {
  local item="$1"
  local stamp
  stamp="$(date +%Y%m%d_%H%M%S)"
  local backup_root="$REPO/backups/$stamp"
  mkdir -p "$backup_root"
  local target="$REPO/$item"
  local dest="$backup_root/$(sanitize "$item")"
  # Ensure parent directories exist for nested paths like vendor/bin/phpstan
  mkdir -p "$(dirname "$dest")"
  if [ -e "$target" ]; then
    cp -r "$target" "$dest" || true
    log "Backup: $target -> $dest"
  else
    log "Backup skipped; path not found: $target"
  fi
}

run_static_analysis() {
  log "Static analysis: begin"
  if command -v php >/dev/null 2>&1; then
    php -v | head -n1 | tee -a "$MASTER_LOG" >/dev/null || true
  else
    log "PHP not found in WSL; analysis skipped"
  fi
  # phpstan
  if [ -x "$REPO/vendor/bin/phpstan" ]; then
    (cd "$REPO" && vendor/bin/phpstan analyze --no-progress) || true
  else
    log "phpstan unavailable; skipped"
  fi
  # psalm
  if [ -x "$REPO/vendor/bin/psalm" ]; then
    (cd "$REPO" && vendor/bin/psalm --no-cache) || true
  else
    log "psalm unavailable; skipped"
  fi
  log "Static analysis: end"
}

run_tests_for_item() {
  local item="$1"
  log "Tests: executing for item '$item'"
  if ! command -v php >/dev/null 2>&1; then
    log "PHP not found; tests skipped"
    return 1
  fi
  if [ -f "$REPO/phpunit.phar" ]; then
    if [[ "$item" == tests/*.php ]]; then
      (cd "$REPO" && php phpunit.phar --configuration phpunit.xml --filter "$(basename "$item" .php)")
    elif [[ "$item" == tests/* ]]; then
      (cd "$REPO" && php phpunit.phar --configuration phpunit.xml --testsuite "$(basename "$item")")
    else
      (cd "$REPO" && php phpunit.phar --configuration phpunit.xml)
    fi
    return $?
  else
    log "phpunit.phar missing; tests skipped"
    return 1
  fi
}

process_item() {
  local item="$1"
  local attempts=0
  local max_attempts=10

  if ! is_action_item "$item"; then
    log "Skip non-action line: $item"
    return 2
  fi

  log "Process: $item"
  backup_item "$item"
  run_static_analysis

  while [ "$attempts" -lt "$max_attempts" ]; do
    attempts=$((attempts+1))
    log "Attempt $attempts: $item"

    if run_tests_for_item "$item"; then
      log "Success: $item"
      echo "$item" >> "$FIXED"
      return 0
    else
      log "Failed attempt $attempts for: $item"
    fi
  done

  log "Unresolved after $max_attempts attempts: $item"
  echo "$item" >> "$FAILED"
  return 1
}

batch_summary() {
  local batch="$1"
  local file="$REPORT_DIR/phase_summary_${batch}.txt"
  log "Batch summary -> $file"
  {
    echo "Batch: $batch"
    echo "Timestamp: $(date -Iseconds)"
    echo "Fixed count: $( [ -f "$FIXED" ] && wc -l < "$FIXED" || echo 0 )"
    echo "Failed count: $( [ -f "$FAILED" ] && wc -l < "$FAILED" || echo 0 )"
  } > "$file"
}

final_summary() {
  local file="$REPORT_DIR/final_full_auto_summary.txt"
  log "Final summary -> $file"
  {
    echo "Final Summary"
    echo "Timestamp: $(date -Iseconds)"
    echo ""
    echo "Fixed items:"
    if [ -f "$FIXED" ]; then cat "$FIXED"; else echo "(none)"; fi
    echo ""
    echo "Unresolvable items:"
    if [ -f "$FAILED" ]; then cat "$FAILED"; else echo "(none)"; fi
  } > "$file"
}

get_next_line_number() {
  awk 'NF && $0 !~ /^[[:space:]]*#/{print NR; exit}' "$INPUT" 2>/dev/null || true
}

get_next_item_text() {
  awk 'NF && $0 !~ /^[[:space:]]*#/{print; exit}' "$INPUT" 2>/dev/null || true
}

remove_line_from_input() {
  local ln="$1"
  local tmp
  tmp="$(mktemp)"
  awk -v ln="$ln" 'NR!=ln' "$INPUT" > "$tmp" && mv "$tmp" "$INPUT"
}

main() {
  ensure_env
  log "=== Full Automated Run Initiated ==="
  if [ ! -f "$INPUT" ]; then
    log "Input list missing: $INPUT"
    final_summary
    log "=== Completed: no input list ==="
    return 0
  fi

  local processed_success=0
  local processed_total=0
  local batch=1
  local max_items="${MAX_ITEMS:-}"

  while true; do
    local ln
    ln="$(get_next_line_number)"
    [ -z "$ln" ] && break

    local item
    item="$(get_next_item_text)"
    [ -z "$item" ] && break

    processed_total=$((processed_total+1))

    if process_item "$item"; then
      processed_success=$((processed_success+1))
      if [ $((processed_success % 25)) -eq 0 ]; then
        batch_summary "$batch"
        batch=$((batch+1))
      fi
    fi

    remove_line_from_input "$ln"

    if [ -n "$max_items" ] && [ "$processed_total" -ge "$max_items" ]; then
      log "MAX_ITEMS=$max_items reached; stopping early"
      break
    fi
  done

  final_summary
  log "=== Full Automated Run Completed ==="
}

main "$@"