# API Endpoint Test Evidence (v1.0 audio-only)

This document records manual validation evidence for the canonical Abilities API endpoint.

## Endpoint under test

- POST /wp-json/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run

## Test run date

- 2026-07-19 (retry fault run on target WordPress: failed response PASS, retry attempts logged)
- 2026-07-19 (retry fault run on target WordPress: fault injection active, retry events observed)
- 2026-07-19 (retry fault run on target WordPress: fault injection inactive)
- 2026-07-19 (WP_DEBUG_LOG inspection on target WordPress)
- 2026-07-19 (real WordPress smoke test passed after S2-02 changes)
- 2026-07-19 (S2-02 observability implementation and local validator pass)
- 2026-07-13 (retry fault-injection 429 passed)
- 2026-07-13 (retry fault-injection 429 attempted, filter inactive)
- 2026-07-13 (idempotency replay test passed: same external_run_id)
- 2026-07-13 (hardened smoke test with structured JSON assertions)
- 2026-07-13 (latest re-validation on real WordPress)
- 2026-07-12 (initial validation)

## Evidence summary

1. Valid request returns successful run output.
2. Response includes expected contract fields.
3. Invalid payload is rejected with HTTP 400 validation error.
4. Replay with the same external_run_id is idempotent.
5. Unauthorized requests are rejected.
6. Retry fault-injection testing requires activation of the dedicated filter.

## Test 1: valid request (happy path)

### Request

- Method: POST
- Body: valid payload with input.source and input.audio configured.

### Expected

- HTTP 200
- Top-level output includes run metadata and generated post data.

### Result

- PASS

### Latest observed successful response (2026-07-13)

- HTTP 200
- status: completed
- run_id present
- post_id: 172
- quality_flags: ["proper_noun_hints_missing"]

### Additional successful run after hardening assertions (2026-07-13)

- HTTP 200
- status: completed
- run_id: run_1DVpIXbaIyj1
- post_id: 173
- quality_flags: ["proper_noun_hints_missing"]
- Invalid payload check: HTTP 400 with code ability_invalid_input

### Real WordPress smoke run after S2-02 changes (2026-07-19)

- HTTP 200
- status: completed
- run_id: run_hJXSx6oClPqQ
- post_id: 180
- debug_reference_id: dbg_dKQhlkmhMIk0
- quality_flags: ["proper_noun_hints_missing"]
- Invalid payload check: HTTP 400 with code ability_invalid_input

## Test 2: contract fields present

### Expected fields

- run_id
- status
- quality_flags
- processing_timestamps
- debug_reference_id

### Result

- PASS

## Test 3: invalid payload (missing source)

### Request body used

```json
{
  "input": {
    "contract_version": "1.0.0",
    "external_run_id": "{{external_run_id}}",
    "audio": {
      "media_id": {{media_id}}
    }
  }
}
```

### Expected

- HTTP 400
- Validation error for invalid input.

### Observed response

```json
{
  "code": "ability_invalid_input",
  "message": "Ability \"audio-converter-for-wp/audio-to-post\" has invalid input. Reason: source is a required property of input.",
  "data": {
    "status": 400
  }
}
```

### Result

- PASS

## Test 4: idempotency replay (same external_run_id)

### Expected

- HTTP 200 on both calls.
- Same run_id on call #1 and call #2.
- Same post_id on call #1 and call #2.
- Status completed on both calls.

### Observed response summary (2026-07-13)

- external_run_id: replay-1783967422
- run_id call #1: run_vqYMT2ZdYkmi
- run_id call #2: run_vqYMT2ZdYkmi
- post_id call #1: 174
- post_id call #2: 174
- status call #1: completed
- status call #2: completed

### Result

- PASS

## Test 5: unauthorized access rejection

### Expected

- Request without auth header is rejected.
- HTTP status is 401 or 403.

### Observed response summary (2026-07-13)

- Response code: rest_ability_cannot_execute
- HTTP status: 401
- Message: Sorry, you are not allowed to execute this ability.

### Result

- PASS

## Test 6: retry fault-injection (429)

### Expected

- With fault-injection filter active, run should fail with `ai_provider_unavailable` after retry policy execution.
- Error payload should be marked as retryable.

### Observed response summary (2026-07-13)

- external_run_id: retry429-1783969059
- HTTP status: 200
- Ability status: failed
- error.code: ai_provider_unavailable
- error.retryable: true
- run_id: run_T71bzqsAtvfm

### Result

- PASS

### Additional production attempt (2026-07-19)

- Triggered run with `external_run_id` prefix `retry429-` on target WordPress.
- Observed log lines:
  - `run_processing_started` for run `run_TAFZB2wy5r3q`
  - `run_completed` for the same run
- Script exit code: `2` (fault injection inactive path).

Interpretation:
- Retry fault-injection filter was not active during this attempt, so no `ai_retry_attempt` / `ai_retry_exhausted` events were expected.
- Production observability confirms lifecycle events; retry/failure observability on production still requires an active MU-plugin injection run.

### Production retry run with active fault injection (2026-07-19)

- Test script exit code: `0` (`test-retry-fault-429.sh` passed).
- Log evidence for `external_run_id=retry429-1784463834`:
  - `run_processing_started` emitted
  - `ai_retry_attempt` emitted for attempt `1/3` with `retryable_reason=rate_limit`
  - `ai_retry_attempt` emitted for attempt `2/3` with `retryable_reason=rate_limit`
- Direct grep for `ai_retry_exhausted|run_failed|ai_non_retryable_error` on production log returned no lines for this run window.

Interpretation:
- Deterministic retry path is active on production and retry telemetry (`retryable_reason`, `attempt`, `max_attempts`) is being emitted.
- Final retry terminal events were not observed in the captured production slice and require one additional capture pass aligned to the exact run timestamp.

### Production retry run (failed result) with log capture (2026-07-19)

- Test script run:
  - `external_run_id=retry429-1784464281`
  - HTTP 200 with structured `status=failed`
  - `error.code=ai_provider_unavailable`
  - `error.retryable=true`
  - `run_id=run_gMsXqfm9O3Gb`
  - `debug_reference_id=dbg_HjBcUY85ORDA`
- Production log lines for same run include:
  - `run_processing_started`
  - `ai_retry_attempt` attempt `1/3`
  - `ai_retry_attempt` attempt `2/3`

Observation:
- Functional behavior is correct (retryable failure returned as expected).
- Current production log extraction still does not show terminal retry lifecycle events (`ai_retry_exhausted`, `run_failed`) for this run window.

## Final status

All planned API endpoint tests in README are complete.

## Sprint 2 S2-02 observability evidence (implementation)

### Scope implemented (2026-07-19)

- Standard lifecycle context normalization (`run_id`, `external_run_id`, `status`, `error_code`, `retryable_reason`, `attempt`, `max_attempts`, `debug_reference_id`).
- Lifecycle events expanded across execute flow (`run_received`, replay variants, lock/release, processing, completed, failed).
- Retry telemetry enriched with stable `error_code` and dedicated terminal events (`ai_retry_exhausted`, `ai_non_retryable_error`).

### Local validation

- Phase 3 validator: PASS
- PHP lint on modified files: PASS

### Remaining operational validation

- Real smoke request on target WordPress: PASS (2026-07-19).

### WP_DEBUG_LOG inspection (2026-07-19)

Environment details:
- `WP_CONTENT_DIR=/www/audioconverter_259/public/wp-content`
- `WP_DEBUG=true`
- `WP_DEBUG_LOG=true`
- `ini.error_log=/www/audioconverter_259/public/wp-content/debug.log`

Observed log sample (same test window):
- `run_processing_started` with `run_id` and `external_run_id`
- `run_completed` with `run_id`, `external_run_id`, and `post_id`
- `aicb-debug-probe` custom probe line present

Conclusion:
- Observability logging is active on target WordPress and lifecycle events are being written.
- Remaining: execute a deterministic retry/failure run on target WordPress and confirm `ai_retry_attempt`/`ai_retry_exhausted` and failed error classification fields in production log output.
