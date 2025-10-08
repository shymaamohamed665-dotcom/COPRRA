#!/usr/bin/env bash
# Simple batch runner: accepts a file with commands (one per line) and runs up to 4 in parallel,
# capturing stdout/stderr/meta into reports/task4/<seq>-<safe-name>/

set -eu
WORKDIR="$(pwd)"
OUTDIR="${WORKDIR}/reports/task4"
mkdir -p "$OUTDIR"
COMMANDS_FILE="$1"
if [ ! -f "$COMMANDS_FILE" ]; then
  echo "Commands file not found: $COMMANDS_FILE"
  exit 1
fi

seq=0
pids=()

safe_name() {
  echo "$1" | sed -E 's/[^A-Za-z0-9._-]/-/g' | cut -c1-60
}

run_cmd() {
  local cmd="$1"
  seq=$((seq+1))
  local name=$(safe_name "$cmd")
  local dir="$OUTDIR/$(printf "%03d" "$seq")-$name"
  mkdir -p "$dir"
  echo "{\"command\": \"$cmd\", \"start\": \"$(date -Iseconds)\"}" > "$dir/meta.json"
  # run in subshell so environment stays consistent
  bash -lc "$cmd" > "$dir/stdout.log" 2> "$dir/stderr.log" || echo $? > "$dir/exit.code"
  echo "{\"end\": \"$(date -Iseconds)\", \"exit_code\": $(cat "$dir/exit.code" 2>/dev/null || echo 0)}" >> "$dir/meta.json"
}

# Read commands and run them in groups of 4
batch=()
while IFS= read -r line || [ -n "$line" ]; do
  # skip empty and comments
  [[ -z "$line" || "$line" =~ ^# ]] && continue
  batch+=("$line")
  if [ "${#batch[@]}" -eq 4 ]; then
    # run the 4
    for c in "${batch[@]}"; do
      run_cmd "$c" &
      pids+=("$!")
    done
    # wait for all pids
    for pid in "${pids[@]}"; do
      wait "$pid" || true
    done
    # reset
    batch=()
    pids=()
  fi
done < "$COMMANDS_FILE"

# run remainder
if [ "${#batch[@]}" -gt 0 ]; then
  for c in "${batch[@]}"; do
    run_cmd "$c" &
    pids+=("$!")
  done
  for pid in "${pids[@]}"; do
    wait "$pid" || true
  done
fi

echo "Batches complete. Outputs saved under $OUTDIR"
