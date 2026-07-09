#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

mkdir -p "$ROOT_DIR/docs/contracts/schemas"
mkdir -p "$ROOT_DIR/docs/contracts/examples"
mkdir -p "$ROOT_DIR/reports/phase2"

echo "[OK] Phase 2 directories ready"
echo "- $ROOT_DIR/docs/contracts/schemas"
echo "- $ROOT_DIR/docs/contracts/examples"
echo "- $ROOT_DIR/reports/phase2"
