# Gap Analysis (Fase 1)

## Matrice Gap

| ID | Gap | Gravita' | Impatto | Evidenza baseline | Azione proposta | Owner |
|---|---|---|---|---|---|---|
| G-001 | Idempotenza forte external_run_id non consolidata | Alta | Doppia pubblicazione | TBD | Definire store + lock + recovery | TBD |
| G-002 | Job lifecycle persistito incompleto | Alta | Operazioni non tracciabili | TBD | State machine + transizioni + retry policy | TBD |
| G-003 | Auth esterna e permission hardening | Alta | Rischio sicurezza | TBD | Security policy + test casi unauthorized | TBD |
| G-004 | Osservabilita' e correlation IDs | Media | Debug lento | TBD | Audit log standard + debug_reference_id | TBD |
| G-005 | Proper noun enforcement | Media | Regressione qualita' contenuti | TBD | Hints + mismatch detection + quality flags | TBD |

## Priorita' Operativa

1. G-001
2. G-002
3. G-003
4. G-004
5. G-005
