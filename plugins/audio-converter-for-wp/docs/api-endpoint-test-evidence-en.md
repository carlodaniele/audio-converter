# API Endpoint Test Evidence (v1.0 audio-only)

This document records manual validation evidence for the canonical Abilities API endpoint.

## Endpoint under test

- POST /wp-json/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run

## Test run date

- 2026-07-12

## Evidence summary

1. Valid request returns successful run output.
2. Response includes expected contract fields.
3. Invalid payload is rejected with HTTP 400 validation error.

## Test 1: valid request (happy path)

### Request

- Method: POST
- Body: valid payload with input.source and input.audio configured.

### Expected

- HTTP 200
- Top-level output includes run metadata and generated post data.

### Result

- PASS

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

## Final status

All planned API endpoint tests in README are complete.
