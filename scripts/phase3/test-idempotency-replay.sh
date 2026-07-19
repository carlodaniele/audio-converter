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

ENDPOINT="${WP_BASE_URL%/}/wp-json/wp-abilities/v1/abilities/audio-converter/audio-to-post/run"
AUTH_HEADER="Authorization: Basic $(printf '%s:%s' "$WP_USER" "$WP_APP_PASSWORD" | base64)"
RUN_TS="$(date +%s)"
EXTERNAL_RUN_ID="replay-${RUN_TS}"
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

extract_json_value() {
  local response_file="$1"
  local key_path="$2"

  php -r '
    $data = json_decode((string) file_get_contents($argv[1]), true);
    if (!is_array($data)) {
      exit(2);
    }

    $current = $data;
    foreach (explode(".", (string) $argv[2]) as $segment) {
      if ($segment === "") {
        continue;
      }

      if (!is_array($current) || !array_key_exists($segment, $current)) {
        exit(3);
      }

      $current = $current[$segment];
    }

    if (is_bool($current)) {
      echo $current ? "true" : "false";
      exit(0);
    }

    if ($current === null) {
      echo "null";
      exit(0);
    }

    if (is_scalar($current)) {
      echo (string) $current;
      exit(0);
    }

    echo json_encode($current);
    exit(0);
  ' "$response_file" "$key_path"
}

call_endpoint() {
  local payload="$1"
  local response_file="$2"

  curl -sS -o "$response_file" -w "%{http_code}" -X POST "$ENDPOINT" \
    -H "Content-Type: application/json" \
    -H "$AUTH_HEADER" \
    --data "$payload"
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
    },
    "proper_noun_hints": ["WordPress", "Gutenberg"]
  }
}
JSON
)

TMP1="$(mktemp)"
TMP2="$(mktemp)"
trap 'rm -f "$TMP1" "$TMP2"' EXIT

echo "[INFO] Call #1 with external_run_id=${EXTERNAL_RUN_ID}"
HTTP1="$(call_endpoint "$PAYLOAD" "$TMP1")"
RESP1="$(cat "$TMP1")"
echo "$RESP1"

echo "[INFO] Call #2 with same external_run_id=${EXTERNAL_RUN_ID}"
HTTP2="$(call_endpoint "$PAYLOAD" "$TMP2")"
RESP2="$(cat "$TMP2")"
echo "$RESP2"

assert_equals "200" "$HTTP1" "Call #1 returns HTTP 200"
assert_equals "200" "$HTTP2" "Call #2 returns HTTP 200"

RUN_ID_1="$(extract_json_value "$TMP1" "run_id")"
RUN_ID_2="$(extract_json_value "$TMP2" "run_id")"
POST_ID_1="$(extract_json_value "$TMP1" "post_id")"
POST_ID_2="$(extract_json_value "$TMP2" "post_id")"
STATUS_1="$(extract_json_value "$TMP1" "status")"
STATUS_2="$(extract_json_value "$TMP2" "status")"

assert_equals "$RUN_ID_1" "$RUN_ID_2" "Replay returns same run_id"
assert_equals "$POST_ID_1" "$POST_ID_2" "Replay returns same post_id"
assert_equals "completed" "$STATUS_1" "Call #1 status is completed"
assert_equals "completed" "$STATUS_2" "Call #2 status is completed"

if [[ "$FAILURES" -gt 0 ]]; then
  echo "[FAIL] Idempotency replay test finished with $FAILURES failure(s)."
  exit 1
fi

echo "[DONE] Idempotency replay test passed."
