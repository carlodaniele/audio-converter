#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PLUGIN_DIR="$ROOT_DIR/plugins/audio-converter"

required_files=(
  "$PLUGIN_DIR/audio-converter.php"
  "$PLUGIN_DIR/includes/class-audio-converter-plugin.php"
  "$PLUGIN_DIR/includes/class-rest-controller.php"
  "$PLUGIN_DIR/includes/class-ability-contract.php"
  "$PLUGIN_DIR/includes/class-job-store.php"
  "$PLUGIN_DIR/includes/class-idempotency-lock.php"
  "$PLUGIN_DIR/includes/class-ai-processor.php"
  "$PLUGIN_DIR/includes/class-normalizer.php"
  "$PLUGIN_DIR/includes/class-block-mapper.php"
  "$PLUGIN_DIR/includes/class-publisher.php"
  "$PLUGIN_DIR/includes/class-observability.php"
)

failed=0
for f in "${required_files[@]}"; do
  if [[ ! -s "$f" ]]; then
    echo "[FAIL] Missing or empty: $f"
    failed=1
  else
    echo "[OK] $f"
  fi
done

if command -v php >/dev/null 2>&1; then
  for f in "${required_files[@]}"; do
    if [[ -f "$f" ]] && [[ "$f" == *.php ]]; then
      if ! php -l "$f" >/dev/null 2>&1; then
        echo "[FAIL] PHP lint: $f"
        failed=1
      else
        echo "[OK] PHP lint: $f"
      fi
    fi
  done
else
  echo "[WARN] php not found: lint skipped"
fi

SUMMARY_FILE="$ROOT_DIR/reports/phase3/skeleton-summary.md"
{
  echo "# Phase 3 Skeleton Summary"
  echo
  echo "Date: $(date '+%Y-%m-%d %H:%M:%S')"
  echo "Plugin: audio-converter"
  echo
  echo "## Files"
  for f in "${required_files[@]}"; do
    echo "- ${f#$ROOT_DIR/}"
  done
} > "$SUMMARY_FILE"

echo "[OK] Summary generated: $SUMMARY_FILE"

if [[ "$failed" -eq 1 ]]; then
  echo "[RESULT] Phase 3 validation FAILED"
  exit 1
fi

echo "[RESULT] Phase 3 validation PASSED"
