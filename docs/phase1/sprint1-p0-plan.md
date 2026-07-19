# Sprint 1 - P0 Plan (S1-01, S1-02)

Data: 2026-07-13
Owner: Carlo Daniele
Stato readiness: GO
Stato sprint: COMPLETED (P0)

## Progress Update

- 2026-07-13: S1-01 COMPLETED. Persistenza run/job su tabella custom + replay idempotenza verificato su WordPress reale.
- 2026-07-13: S1-02 COMPLETED. Retry policy allineata (max 3, errori retryable) + hardening unauthorized verificato.
- Evidenze principali: scripts/phase3/test-idempotency-replay.sh
- Evidenze principali: scripts/phase3/test-unauthorized-access.sh
- Evidenze principali: plugins/audio-converter/docs/api-endpoint-test-evidence-en.md
- Evidenze principali: scripts/phase3/validate-phase3.sh

## S1-01 - Idempotenza forte e job lifecycle persistito

### Obiettivo

Implementare persistenza robusta run/job per prevenire doppia pubblicazione e tracciare transizioni stato in modo recuperabile.

### Input tecnici

- Decisione D-002 (store idempotenza: tabella WP custom + lock transiente)
- Contratto v1 (run_id, status, processing_timestamps)
- Componenti target: class-job-store.php, class-idempotency-lock.php, class-rest-controller.php

### Deliverable

- Schema tabella idempotenza/job definito e applicato.
- Transizioni stato: pending -> processing -> completed/failed persistite.
- Recovery behavior documentato per lock stale/interruzioni.

### DoD testabile

- Test duplicazione: stesso external_run_id non genera doppio post.
- Test replay: seconda richiesta restituisce stato coerente run esistente.
- Test recovery: run interrotta torna in stato consistente senza publish duplicato.

## S1-02 - Security hardening + retry policy eseguibile

### Obiettivo

Applicare policy sicurezza e retry in modo esplicito, testato e allineato alle decisioni D-003 e D-004.

### Input tecnici

- Decisione D-003 (retry exponential backoff breve max 3, solo retryable)
- Decisione D-004 (cutover progressivo con shadow run limitato)
- Componenti target: class-audio-converter-plugin.php, class-rest-controller.php, class-observability.php

### Deliverable

- Matrice errori retryable/non-retryable codificata.
- Flusso retry applicato solo a errori retryable.
- Regole permission/auth documentate con test unauthorized.
- Piano cutover per segmento con metriche e rollback trigger.

### DoD testabile

- Test unauthorized/forbidden su endpoint ability.
- Test retry: errore retryable porta a max 3 tentativi con backoff.
- Test no-retry: errore non retryable fallisce senza loop.
- Test osservabilita': ogni run ha debug_reference_id e log evento chiave.

## Sequenza esecuzione consigliata

1. Implementare S1-01. (Done)
2. Eseguire test S1-01 e fix. (Done)
3. Implementare S1-02. (Done)
4. Eseguire test S1-02 e smoke finale. (Done)

## Milestone di sprint

- M1: S1-01 in verde su test idempotenza/lifecycle. (Completed)
- M2: S1-02 in verde su test sicurezza/retry/observability. (Completed)
- M3: Smoke endpoint finale in verde su ambiente reale. (Completed)
