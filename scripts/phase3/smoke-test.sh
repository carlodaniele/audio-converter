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

RUN_TS="$(date +%s)"
ENDPOINT="${WP_BASE_URL%/}/wp-json/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run"
AUTH_HEADER="Authorization: Basic $(printf '%s:%s' "$WP_USER" "$WP_APP_PASSWORD" | base64)"

FAILURES=0

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

assert_json_has_key() {
  local response_file="$1"
  local key_path="$2"
  local message="$3"

  if php -r '
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

    exit(0);
  ' "$response_file" "$key_path" >/dev/null 2>&1; then
    echo "[PASS] $message"
  else
    echo "[FAIL] $message"
    FAILURES=$((FAILURES + 1))
  fi
}

assert_json_value_equals() {
  local response_file="$1"
  local key_path="$2"
  local expected="$3"
  local message="$4"

  local actual
  actual=$(php -r '
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
  ' "$response_file" "$key_path" 2>/dev/null) || true

  assert_equals "$expected" "$actual" "$message"
}

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

call_endpoint() {
  local endpoint="$1"
  local payload="$2"
  local response_file="$3"

  local http_code
  http_code=$(curl -sS -o "$response_file" -w "%{http_code}" -X POST "$endpoint" \
    -H "Content-Type: application/json" \
    -H "$AUTH_HEADER" \
    --data "$payload")

  echo "$http_code"
}

VALID_RUN_ID="smoke-valid-${RUN_TS}"
VALID_PAYLOAD=$(cat <<JSON
{
  "input": {
    "contract_version": "1.0.0",
    "external_run_id": "$VALID_RUN_ID",
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

INVALID_PAYLOAD=$(cat <<JSON
{
  "input": {
    "contract_version": "1.0.0",
    "external_run_id": "smoke-invalid-${RUN_TS}",
    "audio": {
      "base64": "U29tZSBkZW1vIGJhc2U2NCBjb250ZW50",
      "mime_type": "audio/mpeg"
    }
  }
}
JSON
)

TMP_VALID=$(mktemp)
TMP_INVALID=$(mktemp)
trap 'rm -f "$TMP_VALID" "$TMP_INVALID"' EXIT

echo "[INFO] Test 1/3: Canonical endpoint valid payload"
echo "[INFO] Calling endpoint: $ENDPOINT"
HTTP_VALID=$(call_endpoint "$ENDPOINT" "$VALID_PAYLOAD" "$TMP_VALID")
RESP_VALID=$(cat "$TMP_VALID")
echo "$RESP_VALID"

assert_equals "200" "$HTTP_VALID" "Canonical endpoint returns HTTP 200 for valid payload"
assert_json_value_equals "$TMP_VALID" "contract_version" "1.0.0" "Response contract_version is 1.0.0"
assert_json_has_key "$TMP_VALID" "status" "Response includes status"

echo "[INFO] Test 2/3: Canonical response structure"
assert_json_has_key "$TMP_VALID" "run_id" "Response includes run_id"
assert_json_has_key "$TMP_VALID" "quality_flags" "Response includes quality_flags"
assert_json_has_key "$TMP_VALID" "processing_timestamps" "Response includes processing_timestamps"
assert_json_has_key "$TMP_VALID" "debug_reference_id" "Response includes debug_reference_id"

echo "[INFO] Test 3/3: Invalid payload behavior"
HTTP_INVALID=$(call_endpoint "$ENDPOINT" "$INVALID_PAYLOAD" "$TMP_INVALID")
RESP_INVALID=$(cat "$TMP_INVALID")
echo "$RESP_INVALID"

assert_equals "400" "$HTTP_INVALID" "Canonical endpoint rejects invalid payload with HTTP 400"
assert_json_value_equals "$TMP_INVALID" "code" "ability_invalid_input" "Invalid payload response code is ability_invalid_input"

if [[ "$FAILURES" -gt 0 ]]; then
  echo "[FAIL] Smoke test finished with $FAILURES failure(s)."
  exit 1
fi

echo "[DONE] Smoke test executed successfully."
