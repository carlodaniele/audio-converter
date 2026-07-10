#!/usr/bin/env bash
set -euo pipefail

missing=0

check_path() {
  local path="$1"
  if [[ ! -e "$path" ]]; then
    echo "Missing: $path"
    missing=1
  fi
}

check_path "core/README.md"
check_path "core/CONTRACT.md"
check_path "adapters/wordpress/README.md"
check_path "adapters/astro/README.md"
check_path "docs/architecture-target.md"
check_path "plugins/audio-converter-for-wp/audio-converter-for-wp.php"

if [[ "$missing" -ne 0 ]]; then
  echo "Agnostic structure validation failed."
  exit 1
fi

echo "Agnostic structure validation passed."
