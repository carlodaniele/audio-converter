# Gap Analysis (Fase 1)

## Matrice Gap

| ID | Gap | Gravita' | Impatto | Evidenza baseline | Azione proposta | Owner |
|---|---|---|---|---|---|---|
| G-001 | Idempotenza forte external_run_id non consolidata | Alta | Doppia pubblicazione | docs/phase1/decision-log.md (D-002) | Definire store + lock + recovery (S1-01) | Carlo Daniele |
| G-002 | Job lifecycle persistito incompleto | Alta | Operazioni non tracciabili | docs/phase1/sprint1-p0-plan.md (S1-01) | State machine + transizioni + retry policy (S1-01/S1-02) | Carlo Daniele |
| G-003 | Auth esterna e permission hardening | Alta | Rischio sicurezza | plugins/audio-converter-for-wp/includes/class-audio-converter-plugin.php | Security policy + test casi unauthorized (S1-02) | Carlo Daniele |
| G-004 | Osservabilita' e correlation IDs | Media | Debug lento | plugins/audio-converter-for-wp/includes/class-observability.php | Audit log standard + debug_reference_id (S1-02) | Carlo Daniele |
| G-005 | Proper noun enforcement | Media | Regressione qualita' contenuti | plugins/audio-converter-for-wp/includes/class-rest-controller.php | Hints + mismatch detection + quality flags (hardening post-S1) | Carlo Daniele |

## Priorita' Operativa

1. G-001
2. G-002
3. G-003
4. G-004
5. G-005
