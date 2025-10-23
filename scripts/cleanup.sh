#!/usr/bin/env bash
set -euo pipefail

DEST="storage/reports"
mkdir -p "$DEST"

# Move common debris from repo root to ignored temp dir
PATTERNS=("*.txt" "*.log" "*.out" "test_*.php" "*_report.txt" "actionlint")

for pat in "${PATTERNS[@]}"; do
  for f in ./$pat; do
    [ -e "$f" ] || continue
    if [ -f "$f" ]; then
      echo "Moving $f -> $DEST/"
      mv -f "$f" "$DEST/"
    fi
  done
done

echo "✅ Cleanup complete. Temporary files relocated to $DEST."
