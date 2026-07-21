# Retry Fault Injection Runbook (S2-01)

Use this runbook to validate retry paths deterministically with controlled AI errors.

## Scope

This runbook validates the retry policy implemented in `Audio_Converter_AI_Processor`:

- Max 3 attempts
- Retry only for retryable errors (timeout, 429, 502/503/504)
- No retry for non-retryable errors

## Hook used

Filter name:

- `audio_converter_ai_retry_fault_injection_error`

Context provided:

- `external_run_id`
- `source`
- `stage`
- `attempt`
- `max_attempts`

## Example MU-plugin for 429 simulation

Create `wp-content/mu-plugins/aicb-retry-fault-injection.php`:

```php
<?php
add_filter(
    'audio_converter_ai_retry_fault_injection_error',
    static function ($forced, array $context) {
        if (empty($context['external_run_id']) || strpos($context['external_run_id'], 'retry429-') !== 0) {
            return $forced;
        }

        if (!empty($context['attempt']) && (int) $context['attempt'] <= 3) {
            return new WP_Error('rate_limit', '429 Too Many Requests (fault injection)');
        }

        return $forced;
    },
    10,
    2
);
```

## Test procedure

1. Enable the MU-plugin above.
2. Call canonical endpoint with `external_run_id` prefix `retry429-`.
3. Observe final response and logs.

Expected:

- Final response is failure with `ai_provider_unavailable`.
- Retry attempts stop at max 3.

## Variants

- 503 simulation: return `new WP_Error('service_unavailable', '503 Service Unavailable (fault injection)')`.
- timeout simulation: return `new WP_Error('http_timeout', 'cURL error 28: Operation timed out')`.
- non-retryable simulation: return `new WP_Error('bad_request', '400 Invalid request (fault injection)')` and expect single attempt.

## Cleanup

1. Remove the MU-plugin file after test.
2. Run normal smoke test to verify no fault injection remains active.
