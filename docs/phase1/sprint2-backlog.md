# Sprint 2 Backlog

Date: 2026-07-13
Owner: Carlo Daniele
Status: IN PROGRESS

## Progress Update

- 2026-07-13: S2-01 setup started. Added deterministic fault-injection hook and runbook for retry-path testing.
- Evidence: plugins/audio-converter-for-wp/docs/retry-fault-injection-runbook-en.md
- 2026-07-13: S2-01 validation passed on real WordPress with 429 fault-injection.
- Evidence: scripts/phase3/test-retry-fault-429.sh + plugins/audio-converter-for-wp/docs/api-endpoint-test-evidence-en.md

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
- TODO

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

## Execution Order

1. Implement S2-01.
2. Validate S2-01 with controlled test evidence.
3. Implement S2-02.
4. Validate S2-02 with a real smoke run and log inspection.

## Exit Criteria (Sprint 2)

- S2-01 DoD fully PASS.
- S2-02 DoD fully PASS.
- Phase 3 validator remains PASS after changes.
