#!/usr/bin/env bash
# Parallel runner for Task 4
# - Runs commands from a file, up to CONCURRENCY jobs at once
# - Only preserves outputs (stdout/stderr/meta) for failed runs (exit != 0 or non-empty stderr)
# - Records commands that could not start
# - Writes a summary every 30 finished jobs to reports/task4/summary_batch_<n>.json

set -u
WORKDIR="$(pwd)"
OUTROOT="$WORKDIR/reports/task4"
mkdir -p "$OUTROOT/preserved"
mkdir -p "$OUTROOT/tmp"

CMDFILE="${1:-}"
CONCURRENCY_ARG="${2:-}"
if [ -z "$CMDFILE" ]; then
  echo "Usage: $0 <commands-file> [concurrency]"
  exit 1
fi
if [ ! -f "$CMDFILE" ]; then
  echo "Commands file not found: $CMDFILE"
  exit 1
fi

# determine concurrency: user-specified or nproc*4 capped at 64
if [ -n "$CONCURRENCY_ARG" ]; then
  CONCURRENCY="$CONCURRENCY_ARG"
else
  CORES=$(nproc 2>/dev/null || echo 1)
  CONCURRENCY=$((CORES * 4))
  if [ "$CONCURRENCY" -gt 64 ]; then
    CONCURRENCY=64
  fi
fi

echo "Runner starting: commands=$CMDFILE concurrency=$CONCURRENCY"

# counters
total=0
started=0
finished=0
failed_to_start=0
failed_runs=0
passed_runs=0

# arrays to track jobs
pids=()
seqs=()
cmds=()

safe_name() {
  echo "$1" | sed -E 's/[^A-Za-z0-9._-]/-/g' | cut -c1-80
}

run_one() {
  local seq=$1
  local cmd="$2"
  local tmpdir="$OUTROOT/tmp/$(printf "%06d" "$seq")-$$(date +%s%N)"
  mkdir -p "$tmpdir"
  echo "{\"command\": \"$(echo "$cmd" | sed 's/"/\\"/g')\", \"start\": \"$(date -Iseconds)\"}" > "$tmpdir/meta.json"
  # Execute the command in a subshell
  bash -lc "$cmd" > "$tmpdir/stdout.log" 2> "$tmpdir/stderr.log"
  echo $? > "$tmpdir/exit.code"
  echo "{\"end\": \"$(date -Iseconds)\", \"exit_code\": $(cat "$tmpdir/exit.code") }" >> "$tmpdir/meta.json"
  # Analyze outcome
  local exitc
  exitc=$(cat "$tmpdir/exit.code" 2>/dev/null || echo 1)
  local stderr_nonempty=0
  if [ -s "$tmpdir/stderr.log" ]; then
    stderr_nonempty=1
  fi
  if [ "$exitc" -ne 0 ] || [ "$stderr_nonempty" -eq 1 ]; then
    # preserve
    local name=$(safe_name "$cmd")
    local dest="$OUTROOT/preserved/$(printf "%06d" "$seq")-$name"
    mv "$tmpdir" "$dest"
    echo "$seq,"$cmd",FAILED,$exitc" >> "$OUTROOT/preserved/failed_runs.csv"
    failed_runs=$((failed_runs+1))
  else
    # passed: remove temp (per user instruction do not save positives)
    rm -rf "$tmpdir"
    passed_runs=$((passed_runs+1))
  fi
  finished=$((finished+1))
  # every 30 finished, write summary
  if [ $((finished % 30)) -eq 0 ]; then
    batch_no=$((finished / 30))
    cat > "$OUTROOT/summary_batch_$batch_no.json" <<EOF
{
  "total": $total,
  "started": $started,
  "finished": $finished,
  "failed_runs": $failed_runs,
  "failed_to_start": $failed_to_start,
  "passed_runs": $passed_runs,
  "remaining": $((total - finished))
}
EOF
  fi
}

# producer-consumer style
# Read commands into array (so we can know total)
mapfile -t COMMANDS < "$CMDFILE"
total=${#COMMANDS[@]}

# Start feeding commands
i=0
active=0
while [ $i -lt $total ] || [ $active -gt 0 ]; do
  # start new jobs while under concurrency and commands remain
  while [ $active -lt $CONCURRENCY ] && [ $i -lt $total ]; do
    idx=$((i+1))
    cmd="${COMMANDS[$i]}"
    # quick check that the command is non-empty
    if [ -z "$cmd" ]; then
      i=$((i+1))
      continue
    fi
    started=$((started+1))
    # run in background and track pid
    run_one $idx "$cmd" &
    pid=$!
    pids+=("$pid")
    seqs+=("$idx")
    cmds+=("$cmd")
    active=$((active+1))
    i=$((i+1))
  done

  # wait for any job to finish
  if [ ${#pids[@]} -gt 0 ]; then
    # wait for one to finish
    wait -n
    # clean up finished pids array by checking active processes
    new_pids=()
    new_seqs=()
    new_cmds=()
    for j in "${!pids[@]}"; do
      pid=${pids[$j]}
      if kill -0 "$pid" 2>/dev/null; then
        new_pids+=("$pid")
        new_seqs+=("${seqs[$j]}")
        new_cmds+=("${cmds[$j]}")
      fi
    done
    pids=("${new_pids[@]}")
    seqs=("${new_seqs[@]}")
    cmds=("${new_cmds[@]}")
    # recalc active
    active=${#pids[@]}
  else
    # no active pids, small sleep to avoid busy loop
    sleep 0.1
  fi
done

# Final summary
cat > "$OUTROOT/summary_final.json" <<EOF
{
  "total": $total,
  "started": $started,
  "finished": $finished,
  "failed_runs": $failed_runs,
  "failed_to_start": $failed_to_start,
  "passed_runs": $passed_runs,
  "remaining": $((total - finished))
}
EOF

echo "Run complete. Final summary: $OUTROOT/summary_final.json"
