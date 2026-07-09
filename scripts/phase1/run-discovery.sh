#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
REPOS_DIR="$ROOT_DIR/references/repos"
REPORT_DIR="$ROOT_DIR/reports/phase1"

AICB_REPO_URL="https://github.com/carlodaniele/ai-content-builder.git"
AICBN_REPO_URL="https://github.com/carlodaniele/ai-content-builder-next.git"

mkdir -p "$REPOS_DIR" "$REPORT_DIR"

search_count() {
  local pattern="$1"
  local target="$2"

  if command -v rg >/dev/null 2>&1; then
    (rg -n "$pattern" "$target" 2>/dev/null || true) | wc -l | tr -d ' '
    return
  fi

  (grep -REn "$pattern" "$target" 2>/dev/null || true) | wc -l | tr -d ' '
}

sync_repo() {
  local url="$1"
  local name="$2"
  local target="$REPOS_DIR/$name"

  if [[ -d "$target/.git" ]]; then
    echo "[INFO] Updating $name"
    git -C "$target" pull --ff-only || echo "[WARN] Cannot update $name (offline or diverged)"
  else
    echo "[INFO] Cloning $name"
    git clone "$url" "$target" || echo "[WARN] Cannot clone $name (offline?)"
  fi
}

sync_repo "$AICB_REPO_URL" "ai-content-builder"
sync_repo "$AICBN_REPO_URL" "ai-content-builder-next"

SUMMARY_FILE="$REPORT_DIR/discovery-summary.md"

{
  echo "# Discovery Summary"
  echo
  echo "Date: $(date '+%Y-%m-%d %H:%M:%S')"
  echo
  echo "## Sources"
  for d in "$REPOS_DIR"/*; do
    [[ -d "$d" ]] || continue
    if [[ -d "$d/.git" ]]; then
      echo "- Repo: $(basename "$d")"
      echo "  - Last commit: $(git -C "$d" log -1 --pretty=format:'%h %s (%ci)' 2>/dev/null || echo 'N/A')"
    fi
  done
  echo
  echo "## Quick Signals"
  for repo in ai-content-builder ai-content-builder-next; do
    target="$REPOS_DIR/$repo"
    if [[ -d "$target" ]]; then
      abilities_count=$(search_count "wp_register_ability|wp_get_ability|wp_has_ability" "$target")
      ai_client_count=$(search_count "wp_ai_client_prompt|using_model_preference|as_json_response" "$target")
      echo "- $repo"
      echo "  - Abilities API references: $abilities_count"
      echo "  - AI Client references: $ai_client_count"
    fi
  done
} > "$SUMMARY_FILE"

echo "[OK] Discovery summary generated: $SUMMARY_FILE"
