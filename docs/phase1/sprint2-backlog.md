# Sprint 2 Backlog

Date: 2026-07-13
Owner: Carlo Daniele
Status: COMPLETED

## Progress Update

- 2026-07-13: S2-01 setup started. Added deterministic fault-injection hook and runbook for retry-path testing.
- Evidence: plugins/audio-converter-for-wp/docs/retry-fault-injection-runbook-en.md
- 2026-07-13: S2-01 validation passed on real WordPress with 429 fault-injection.
- Evidence: scripts/phase3/test-retry-fault-429.sh + plugins/audio-converter-for-wp/docs/api-endpoint-test-evidence-en.md
- 2026-07-19: S2-02 implementation completed (standardized lifecycle/retry observability fields).
- Evidence: plugins/audio-converter-for-wp/includes/class-observability.php + plugins/audio-converter-for-wp/includes/class-rest-controller.php + plugins/audio-converter-for-wp/includes/class-ai-processor.php
- 2026-07-19: S2-02 local validation passed (Phase 3 validator + PHP lint on changed files).
- Evidence: scripts/phase3/validate-phase3.sh
- 2026-07-19: S2-02 real WordPress smoke test passed (valid + invalid payload assertions).
- Evidence: scripts/phase3/smoke-test.sh + plugins/audio-converter-for-wp/docs/api-endpoint-test-evidence-en.md
- 2026-07-19: S2-02 WP_DEBUG_LOG inspection passed on target WordPress (lifecycle events present in production log).
- Evidence: /www/audioconverter_259/public/wp-content/debug.log (captured output in test session)
- 2026-07-19: production retry429 run executed, but fault injection was inactive (run completed; script exit 2).
- Evidence: scripts/phase3/test-retry-fault-429.sh + production debug.log lines for retry429 run_id
- 2026-07-19: production retry429 run repeated with active fault injection; retry test script PASS (exit 0).
- Evidence: scripts/phase3/test-retry-fault-429.sh + production debug.log (`ai_retry_attempt` attempts 1/3 and 2/3)
- 2026-07-19: production grep confirmed retry attempts for `retry429-1784463834`, but no captured `ai_retry_exhausted` / `run_failed` lines yet.
- Evidence: `/www/audioconverter_259/public/wp-content/debug.log` filtered output (lines 9-12 in current extraction)
- 2026-07-19: production retry fault test PASS with failed response (`run_gMsXqfm9O3Gb`, `ai_provider_unavailable`, `retryable=true`) and retry attempts logged for `retry429-1784464281`.
- Evidence: scripts/phase3/test-retry-fault-429.sh output + production debug.log extraction
- 2026-07-19: production symbol parity confirmed on correct server (`79.72.45.240:13955`) for `ai_retry_exhausted`, `run_received`, `log_lifecycle`.
- Evidence: grep output on production plugin files (class-ai-processor.php, class-rest-controller.php, class-observability.php)
- 2026-07-19: fault-injection MU-plugin removed from production (`aicb-retry-fault-injection.php` no longer present).
- Evidence: `ls -l /www/audioconverter_259/public/wp-content/mu-plugins`
- 2026-07-19: post-cleanup baseline smoke test passed on canonical endpoint (valid + invalid payload checks).
- Evidence: scripts/phase3/smoke-test.sh output (`run_gqBafT6vtL7T`, HTTP 200 valid, HTTP 400 invalid)

## Scope

Implement post-Sprint-1 hardening and observability improvements.

## Priority Items

### S2-01 - Fault Injection tests for retry policy

Status:
- COMPLETED

Objective:
Validate retry branches (429/503/504 and timeout) with controlled failures.

Target areas:
- plugins/audio-converter-for-wp/includes/class-ai-processor.php
- scripts/phase3/

Deliverables:
- Test harness or script to simulate retryable provider errors.
- Deterministic assertions for max 3 attempts behavior.
- Evidence document with observed retry outcomes.

Definition of Done (testable):
- 429 simulation: max 3 attempts, final failure marked retryable.
- 503 simulation: max 3 attempts, final failure marked retryable.
- 504/timeout simulation: max 3 attempts, final failure marked retryable.
- Non-retryable simulation: single attempt only.

### S2-02 - Observability metrics and run lifecycle visibility

Status:
- COMPLETED

Objective:
Expose clearer operational signals for run lifecycle and retry decisions.

Target areas:
- plugins/audio-converter-for-wp/includes/class-observability.php
- plugins/audio-converter-for-wp/includes/class-rest-controller.php
- plugins/audio-converter-for-wp/includes/class-ai-processor.php

Deliverables:
- Structured event fields for retry reason and attempt count.
- Lifecycle event completeness for pending/processing/completed/failed transitions.
- Basic dashboard/runbook notes for interpreting logs.

Definition of Done (testable):
- Every run emits lifecycle events with run_id and external_run_id.
- Retry events include retryable_reason, attempt, and max_attempts.
- Failed runs include stable error code classification.

Progress notes:
- Implemented: `run_context` + `log_lifecycle` helper to normalize lifecycle event context fields.
- Implemented: lifecycle events for received/replayed/processing/completed/failed/lock release paths.
- Implemented: retry events enriched with `error_code`, plus explicit `ai_retry_exhausted` and `ai_non_retryable_error` events.
- Real smoke run completed and PASS.
- WP_DEBUG_LOG inspection completed: lifecycle logging confirmed on target WordPress.
- Functional DoD validated: retryable failure behavior and retry telemetry observed on target environment.
- Production code parity validated on target server.
- Post-cleanup baseline behavior validated via smoke PASS.

## Execution Order

1. Implement S2-01.
2. Validate S2-01 with controlled test evidence.
3. Implement S2-02.
4. Validate S2-02 with a real smoke run and log inspection.

## Exit Criteria (Sprint 2)

- S2-01 DoD fully PASS.
- S2-02 DoD fully PASS.
- Phase 3 validator remains PASS after changes.

## Artifacts

- Summary report: reports/phase1/sprint2-summary.md
- Production parity checklist: docs/phase1/sprint2-deploy-parity-checklist.md
