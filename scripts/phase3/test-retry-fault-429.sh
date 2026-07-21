#!/usr/bin/env bash
set -euo pipefail

# Required env:
# WP_BASE_URL      e.g. https://example.com
# WP_USER          WordPress username for Application Password auth
# WP_APP_PASSWORD  Application Password value

if [[ -z "${WP_BASE_URL:-}" || -z "${WP_USER:-}" || -z "${WP_APP_PASSWORD:-}" ]]; then
  echo "[FAIL] Missing env vars. Required: WP_BASE_URL, WP_USER, WP_APP_PASSWORD"
  exit 1
fi

ENDPOINT="${WP_BASE_URL%/}/wp-json/wp-abilities/v1/abilities/nomad-pipeline-audio-to-draft/audio-to-post/run"
AUTH_HEADER="Authorization: Basic $(printf '%s:%s' "$WP_USER" "$WP_APP_PASSWORD" | base64)"
RUN_TS="$(date +%s)"
EXTERNAL_RUN_ID="retry429-${RUN_TS}"
FAILURES=0

assert_equals() {
  local expected="$1"
  local actual="$2"
  local message="$3"

  if [[ "$expected" == "$actual" ]]; then
    echo "[PASS] $message"
  else
    echo "[FAIL] $message (expected=$expected actual=$actual)"
    FAILURES=$((FAILURES + 1))
  fi
}

assert_contains() {
  local haystack="$1"
  local needle="$2"
  local message="$3"

  if [[ "$haystack" == *"$needle"* ]]; then
    echo "[PASS] $message"
  else
    echo "[FAIL] $message"
    FAILURES=$((FAILURES + 1))
  fi
}

PAYLOAD=$(cat <<JSON
{
  "input": {
    "contract_version": "1.0.0",
    "external_run_id": "${EXTERNAL_RUN_ID}",
    "source": "manual",
    "audio": {
      "base64": "U29tZSBkZW1vIGJhc2U2NCBjb250ZW50",
      "mime_type": "audio/mpeg"
    },
    "editorial_options": {
      "language": "en-US",
      "tone": "professional",
      "target_length": "short"
    }
  }
}
JSON
)

TMP_RESPONSE="$(mktemp)"
trap 'rm -f "$TMP_RESPONSE"' EXIT

echo "[INFO] Calling endpoint with fault-injection external_run_id=${EXTERNAL_RUN_ID}"
HTTP_CODE=$(curl -sS -o "$TMP_RESPONSE" -w "%{http_code}" -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "$AUTH_HEADER" \
  --data "$PAYLOAD")

RESP_BODY="$(cat "$TMP_RESPONSE")"
echo "$RESP_BODY"

if [[ "$RESP_BODY" == *'"status":"completed"'* ]]; then
  echo "[FAIL] Fault injection appears inactive (request completed)."
  echo "[INFO] Ensure MU-plugin/filter from retry-fault-injection-runbook-en.md is active."
  exit 2
fi

assert_equals "200" "$HTTP_CODE" "Endpoint returns HTTP 200 with structured failed result"
assert_contains "$RESP_BODY" '"status":"failed"' "Response status is failed"
assert_contains "$RESP_BODY" '"code":"ai_provider_unavailable"' "Response error code is ai_provider_unavailable"
assert_contains "$RESP_BODY" '"retryable":true' "Response marks error as retryable"

if [[ "$FAILURES" -gt 0 ]]; then
  echo "[FAIL] Retry fault 429 test finished with $FAILURES failure(s)."
  exit 1
fi

echo "[DONE] Retry fault 429 test passed."
