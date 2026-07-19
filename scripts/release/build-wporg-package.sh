#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PLUGIN_DIR="$ROOT_DIR/plugins/audio-converter"
README_TXT="$PLUGIN_DIR/readme.txt"
ASSETS_DIR="$PLUGIN_DIR/assets"
OUTPUT_DIR="$ROOT_DIR/dist/wporg"

if [[ ! -f "$README_TXT" ]]; then
  echo "[FAIL] Missing readme.txt at $README_TXT"
  exit 1
fi

STABLE_TAG="$(awk -F': ' 'tolower($1) == "stable tag" {print $2}' "$README_TXT" | tr -d '[:space:]')"
if [[ -z "$STABLE_TAG" ]]; then
  echo "[FAIL] Could not read Stable tag from $README_TXT"
  exit 1
fi

REQUIRED_FILES=(
  "$ASSETS_DIR/images/icon-128x128.png"
  "$ASSETS_DIR/images/icon-256x256.png"
  "$ASSETS_DIR/images/banner-772x250.png"
  "$ASSETS_DIR/images/banner-1544x500.png"
  "$ASSETS_DIR/wporg-screenshots/screenshot-1.png"
  "$ASSETS_DIR/wporg-screenshots/screenshot-2.png"
  "$ASSETS_DIR/wporg-screenshots/screenshot-3.png"
  "$ASSETS_DIR/wporg-screenshots/screenshot-4.png"
  "$ASSETS_DIR/wporg-screenshots/screenshot-5.png"
)

for file in "${REQUIRED_FILES[@]}"; do
  if [[ ! -f "$file" ]]; then
    echo "[FAIL] Missing required file: $file"
    exit 1
  fi
done

mkdir -p "$OUTPUT_DIR"

ZIP_NAME="audio-converter-${STABLE_TAG}.zip"
ZIP_PATH="$OUTPUT_DIR/$ZIP_NAME"
SHA_PATH="$ZIP_PATH.sha256"

rm -f "$ZIP_PATH" "$SHA_PATH"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

rsync -a \
  --exclude '.DS_Store' \
  --exclude '__MACOSX' \
  --exclude '._*' \
  --exclude '.git' \
  --exclude '.github' \
  --exclude 'node_modules' \
  "$PLUGIN_DIR/" "$TMP_DIR/audio-converter/"

# Defensive cleanup for macOS metadata in case files are created after sync.
find "$TMP_DIR/audio-converter" \( -name '.DS_Store' -o -name '._*' \) -delete

( cd "$TMP_DIR" && zip -rq "$ZIP_PATH" "audio-converter" )

shasum -a 256 "$ZIP_PATH" > "$SHA_PATH"

echo "[DONE] Built WP.org package"
echo "[INFO] Stable tag: $STABLE_TAG"
echo "[INFO] Zip: $ZIP_PATH"
echo "[INFO] SHA256: $SHA_PATH"
