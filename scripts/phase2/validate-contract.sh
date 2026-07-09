#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

required_files=(
  "$ROOT_DIR/docs/contracts/ability-audio-to-post-v1.md"
  "$ROOT_DIR/docs/contracts/error-model.md"
  "$ROOT_DIR/docs/contracts/schemas/input-v1.json"
  "$ROOT_DIR/docs/contracts/schemas/output-v1.json"
  "$ROOT_DIR/docs/contracts/examples/request-valid.json"
  "$ROOT_DIR/docs/contracts/examples/response-success.json"
  "$ROOT_DIR/docs/contracts/examples/response-failed.json"
)

missing=0
for f in "${required_files[@]}"; do
  if [[ ! -s "$f" ]]; then
    echo "[FAIL] Missing or empty: $f"
    missing=1
  else
    echo "[OK] $f"
  fi
done

if command -v jq >/dev/null 2>&1; then
  for json_file in \
    "$ROOT_DIR/docs/contracts/schemas/input-v1.json" \
    "$ROOT_DIR/docs/contracts/schemas/output-v1.json" \
    "$ROOT_DIR/docs/contracts/examples/request-valid.json" \
    "$ROOT_DIR/docs/contracts/examples/response-success.json" \
    "$ROOT_DIR/docs/contracts/examples/response-failed.json"; do
    if ! jq empty "$json_file" >/dev/null 2>&1; then
      echo "[FAIL] Invalid JSON: $json_file"
      missing=1
    else
      echo "[OK] Valid JSON: $json_file"
    fi
  done
else
  echo "[WARN] jq not found: JSON syntax check skipped"
fi

SUMMARY_FILE="$ROOT_DIR/reports/phase2/contract-summary.md"
{
  echo "# Contract Summary"
  echo
  echo "Date: $(date '+%Y-%m-%d %H:%M:%S')"
  echo "Ability: audio-converter-for-wp/audio-to-post"
  echo "Contract Version: 1.0.0"
  echo
  echo "## Files"
  for f in "${required_files[@]}"; do
    echo "- ${f#$ROOT_DIR/}"
  done
} > "$SUMMARY_FILE"

echo "[OK] Summary generated: $SUMMARY_FILE"

if [[ "$missing" -eq 1 ]]; then
  echo "[RESULT] Phase 2 contract validation FAILED"
  exit 1
fi

echo "[RESULT] Phase 2 contract validation PASSED"
