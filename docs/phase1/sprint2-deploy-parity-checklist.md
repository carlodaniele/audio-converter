# Sprint 2 Deploy Parity Checklist

Date: 2026-07-19
Owner: Carlo Daniele
Purpose: close production parity checks for S2-02 observability logging.

## Preconditions

- WordPress CLI available on target host.
- WP debug logging enabled (`WP_DEBUG=true`, `WP_DEBUG_LOG=true`).
- Retry fault-injection MU-plugin available for deterministic 429 simulation.

## Step 1: Confirm production plugin symbols

Run on target host:

```bash
grep -En "ai_retry_exhausted|run_received|log_lifecycle" \
  /www/audioconverter_259/public/wp-content/plugins/audio-converter/includes/class-ai-processor.php \
  /www/audioconverter_259/public/wp-content/plugins/audio-converter/includes/class-rest-controller.php \
  /www/audioconverter_259/public/wp-content/plugins/audio-converter/includes/class-observability.php
```

Expected:
- Non-empty output with matching symbols.

If empty:
- Production plugin revision is older than S2-02 logging implementation.
- Deploy latest plugin revision before continuing.

## Step 2: Run deterministic retry test

Run from repository workspace:

```bash
cd /Users/carlo/Documents/audio-converter
./scripts/phase3/test-retry-fault-429.sh
```

Expected:
- Exit code `0`.
- JSON response has `status=failed`, `error.code=ai_provider_unavailable`, `error.retryable=true`.

## Step 3: Capture production logs

Run on target host immediately after Step 2:

```bash
tail -n 1500 /www/audioconverter_259/public/wp-content/debug.log | \
  grep -E "retry429-|ai_retry_attempt|ai_retry_exhausted|run_failed|ai_non_retryable_error|run_completed"
```

Expected (minimum):
- `ai_retry_attempt` lines with `attempt` and `max_attempts`.

Expected (terminal parity target):
- `ai_retry_exhausted` and `run_failed` for the same retry run window.

## Step 4: Baseline smoke after fault test

Run from repository workspace:

```bash
cd /Users/carlo/Documents/audio-converter
./scripts/phase3/smoke-test.sh
```

Expected:
- Exit code `0`.
- Valid request passes and invalid request returns HTTP 400 with `ability_invalid_input`.

## Step 5: Cleanup fault injection

Run on target host:

```bash
rm -f /www/audioconverter_259/public/wp-content/mu-plugins/aicb-retry-fault-injection.php
```

Then rerun Step 4 and confirm normal behavior.

## Exit Criteria

- Functional behavior validated (`test-retry-fault-429.sh` PASS).
- Production logs show retry attempts for deterministic run.
- Production symbol check confirms S2-02 observability revision.
- Fault injection removed and baseline smoke PASS.
