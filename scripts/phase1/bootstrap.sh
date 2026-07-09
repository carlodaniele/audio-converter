#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

mkdir -p "$ROOT_DIR/docs/phase1"
mkdir -p "$ROOT_DIR/references/repos"
mkdir -p "$ROOT_DIR/reports/phase1"

echo "[OK] Directory structure ready"
echo "- $ROOT_DIR/docs/phase1"
echo "- $ROOT_DIR/references/repos"
echo "- $ROOT_DIR/reports/phase1"
