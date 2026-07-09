#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

mkdir -p "$ROOT_DIR/plugins/audio-converter-for-wp/includes"
mkdir -p "$ROOT_DIR/reports/phase3"
mkdir -p "$ROOT_DIR/docs/phase3"

echo "[OK] Phase 3 directories ready"
echo "- $ROOT_DIR/plugins/audio-converter-for-wp/includes"
echo "- $ROOT_DIR/reports/phase3"
echo "- $ROOT_DIR/docs/phase3"
