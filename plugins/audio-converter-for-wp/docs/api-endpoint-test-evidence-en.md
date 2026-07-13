# API Endpoint Test Evidence (v1.0 audio-only)

This document records manual validation evidence for the canonical Abilities API endpoint.

## Endpoint under test

- POST /wp-json/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run

## Test run date

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

## Final status

All planned API endpoint tests in README are complete.
