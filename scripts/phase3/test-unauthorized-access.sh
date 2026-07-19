#!/usr/bin/env bash
set -euo pipefail

# Required env:
# WP_BASE_URL      e.g. https://example.com

if [[ -z "${WP_BASE_URL:-}" ]]; then
  echo "[FAIL] Missing env var. Required: WP_BASE_URL"
  exit 1
fi

ENDPOINT="${WP_BASE_URL%/}/wp-json/wp-abilities/v1/abilities/audio-converter/audio-to-post/run"
RUN_TS="$(date +%s)"
FAILURES=0

assert_http_forbidden_or_unauthorized() {
  local actual="$1"
  local message="$2"

  if [[ "$actual" == "401" || "$actual" == "403" ]]; then
    echo "[PASS] $message"
  else
    echo "[FAIL] $message (expected=401|403 actual=$actual)"
    FAILURES=$((FAILURES + 1))
  fi
}

assert_contains_any() {
  local haystack="$1"
  local message="$2"
  shift 2

  local token
  for token in "$@"; do
    if [[ "$haystack" == *"$token"* ]]; then
      echo "[PASS] $message"
      return
    fi
  done

  echo "[FAIL] $message"
  FAILURES=$((FAILURES + 1))
}

PAYLOAD=$(cat <<JSON
{
  "input": {
    "contract_version": "1.0.0",
    "external_run_id": "unauth-${RUN_TS}",
    "source": "manual",
    "audio": {
      "base64": "U29tZSBkZW1vIGJhc2U2NCBjb250ZW50",
      "mime_type": "audio/mpeg"
    }
  }
}
JSON
)

TMP_RESPONSE="$(mktemp)"
trap 'rm -f "$TMP_RESPONSE"' EXIT

echo "[INFO] Calling endpoint without auth header"
HTTP_CODE=$(curl -sS -o "$TMP_RESPONSE" -w "%{http_code}" -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  --data "$PAYLOAD")

RESP_BODY="$(cat "$TMP_RESPONSE")"
echo "$RESP_BODY"

assert_http_forbidden_or_unauthorized "$HTTP_CODE" "Unauthorized call is rejected"
assert_contains_any "$RESP_BODY" "Unauthorized response contains security error code" "unauthorized" "rest_forbidden" "rest_not_logged_in" "rest_ability_cannot_execute"

if [[ "$FAILURES" -gt 0 ]]; then
  echo "[FAIL] Unauthorized access test finished with $FAILURES failure(s)."
  exit 1
fi

echo "[DONE] Unauthorized access test passed."
