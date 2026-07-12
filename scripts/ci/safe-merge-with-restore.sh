#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  scripts/ci/safe-merge-with-restore.sh <pr-number> [options]

Options:
  --repo <owner/name>       Repository slug. Default: current gh repo.
  --base <branch>           Protected base branch. Default: main.
  --method <squash|merge|rebase>
                            Merge method. Default: squash.
  --keep-branch             Do not delete branch after merge.
  --help                    Show this help.

Behavior:
  1) Read current required_pull_request_reviews protection on base branch.
  2) Temporarily remove required reviews.
  3) Merge PR.
  4) Always restore review protection settings (even if merge fails).
EOF
}

log() {
  echo "[safe-merge] $*"
}

fail() {
  echo "[safe-merge][ERROR] $*" >&2
  exit 1
}

require_cmd() {
  local cmd="$1"
  command -v "$cmd" >/dev/null 2>&1 || fail "Missing required command: $cmd"
}

require_cmd gh

if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
  usage
  exit 0
fi

PR_NUMBER="${1:-}"
if [[ -z "$PR_NUMBER" ]]; then
  usage
  fail "PR number is required."
fi
shift || true

REPO=""
BASE_BRANCH="main"
MERGE_METHOD="squash"
DELETE_BRANCH=1

while [[ $# -gt 0 ]]; do
  case "$1" in
    --repo)
      REPO="${2:-}"
      shift 2
      ;;
    --base)
      BASE_BRANCH="${2:-}"
      shift 2
      ;;
    --method)
      MERGE_METHOD="${2:-}"
      shift 2
      ;;
    --keep-branch)
      DELETE_BRANCH=0
      shift
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      fail "Unknown argument: $1"
      ;;
  esac
done

case "$MERGE_METHOD" in
  squash|merge|rebase)
    ;;
  *)
    fail "Invalid --method value: $MERGE_METHOD"
    ;;
esac

if [[ -z "$REPO" ]]; then
  REPO="$(gh repo view --json nameWithOwner --jq '.nameWithOwner')"
fi

log "Repository: $REPO"
log "Base branch: $BASE_BRANCH"
log "PR: #$PR_NUMBER"
log "Method: $MERGE_METHOD"

SETTINGS_ENDPOINT="repos/$REPO/branches/$BASE_BRANCH/protection/required_pull_request_reviews"

# Fetch current settings so we can restore exactly what existed.
if ! gh api "$SETTINGS_ENDPOINT" >/dev/null 2>&1; then
  fail "Cannot read required_pull_request_reviews for $REPO:$BASE_BRANCH"
fi

DISMISS_STALE_REVIEWS="$(gh api "$SETTINGS_ENDPOINT" --jq '.dismiss_stale_reviews')"
REQUIRE_CODE_OWNER_REVIEWS="$(gh api "$SETTINGS_ENDPOINT" --jq '.require_code_owner_reviews')"
REQUIRE_LAST_PUSH_APPROVAL="$(gh api "$SETTINGS_ENDPOINT" --jq '.require_last_push_approval')"
REQUIRED_APPROVING_REVIEW_COUNT="$(gh api "$SETTINGS_ENDPOINT" --jq '.required_approving_review_count')"

REVIEWS_DISABLED=0

restore_reviews() {
  if [[ "$REVIEWS_DISABLED" -ne 1 ]]; then
    return 0
  fi

  log "Restoring required pull request reviews protection"
  gh api -X PATCH "$SETTINGS_ENDPOINT" \
    -F dismiss_stale_reviews="$DISMISS_STALE_REVIEWS" \
    -F require_code_owner_reviews="$REQUIRE_CODE_OWNER_REVIEWS" \
    -F require_last_push_approval="$REQUIRE_LAST_PUSH_APPROVAL" \
    -F required_approving_review_count="$REQUIRED_APPROVING_REVIEW_COUNT" >/dev/null

  REVIEWS_DISABLED=0
  log "Protection restored"
}

trap restore_reviews EXIT

log "Temporarily disabling required pull request reviews"
gh api -X DELETE "$SETTINGS_ENDPOINT" >/dev/null
REVIEWS_DISABLED=1

MERGE_FLAG="--$MERGE_METHOD"
MERGE_ARGS=("$PR_NUMBER" "$MERGE_FLAG")

if [[ "$DELETE_BRANCH" -eq 1 ]]; then
  MERGE_ARGS+=("--delete-branch")
fi

log "Merging PR #$PR_NUMBER"
gh pr merge "${MERGE_ARGS[@]}"

log "Merge completed"
