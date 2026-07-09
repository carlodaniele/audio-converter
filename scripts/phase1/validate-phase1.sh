#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

required_files=(
  "$ROOT_DIR/docs/phase1/fase1-checklist.md"
  "$ROOT_DIR/docs/phase1/pattern-inventory.md"
  "$ROOT_DIR/docs/phase1/gap-analysis.md"
  "$ROOT_DIR/docs/phase1/decision-log.md"
  "$ROOT_DIR/reports/phase1/discovery-summary.md"
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

if [[ "$missing" -eq 1 ]]; then
  echo "[RESULT] Phase 1 validation FAILED"
  exit 1
fi

echo "[RESULT] Phase 1 validation PASSED"
