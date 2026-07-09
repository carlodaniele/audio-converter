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

RUN_ID="smoke-$(date +%s)"
ENDPOINT="${WP_BASE_URL%/}/wp-json/audio-converter-for-wp/v1/audio-to-post"
AUTH_HEADER="Authorization: Basic $(printf '%s:%s' "$WP_USER" "$WP_APP_PASSWORD" | base64)"

PAYLOAD=$(cat <<JSON
{
  "contract_version": "1.0.0",
  "external_run_id": "$RUN_ID",
  "source": "manual",
  "audio": {
    "base64": "U29tZSBkZW1vIGJhc2U2NCBjb250ZW50",
    "mime_type": "audio/mpeg"
  },
  "editorial_options": {
    "language": "en-US",
    "tone": "professional",
    "target_length": "short"
  },
  "proper_noun_hints": ["WordPress", "Gutenberg"]
}
JSON
)

echo "[INFO] Calling endpoint: $ENDPOINT"
RESP=$(curl -sS -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "$AUTH_HEADER" \
  --data "$PAYLOAD")

echo "[INFO] Response #1"
echo "$RESP"

echo "[INFO] Replaying same external_run_id for idempotency check"
RESP2=$(curl -sS -X POST "$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "$AUTH_HEADER" \
  --data "$PAYLOAD")

echo "[INFO] Response #2"
echo "$RESP2"

echo "[DONE] Smoke test executed"
