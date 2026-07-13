# Decision Log (Fase 1)

## Decisioni Chiuse

| Data | ID | Decisione | Motivazione | Impatto |
|---|---|---|---|---|
| 2026-07-07 | D-001 | Modalita' WordPress-first | Riduce rischio e variabili simultanee | Accelera validazione commerciale |
| 2026-07-13 | D-002 (ex O-001) | Store idempotenza: tabella WP custom + lock transiente | Persistenza run affidabile e prevenzione doppie pubblicazioni anche con riavvii runtime. | Riduzione rischio duplicati e recovery operativo migliorato |
| 2026-07-13 | D-003 (ex O-002) | Retry policy: exponential backoff breve (max 3) solo errori retryable | Bilancia robustezza e costo evitando retry aggressivi su errori non recuperabili. | Maggiore affidabilita' senza escalation di carico/costi |
| 2026-07-13 | D-004 (ex O-003) | Cutover progressivo per segmento con shadow run limitato iniziale | Mitiga il rischio in produzione con confronto output/metriche prima del rollout pieno. | Rollout controllato con rollback piu' sicuro |

## Decisioni Aperte

| Data | ID | Tema | Opzioni | Decision owner | Scadenza |
|---|---|---|---|---|---|
| - | - | Nessuna decisione aperta attiva | - | - | - |

## Proposta Operativa (approvata)

Data proposta: 2026-07-13

| ID | Scelta consigliata | Motivazione sintetica | Owner proposto | Scadenza proposta |
|---|---|---|---|---|
| O-001 | Tabella WP custom per idempotenza + lock transiente in memoria | Riduce il rischio di doppia pubblicazione e rende recuperabile lo stato run oltre la vita dei transienti. | Carlo Daniele | 2026-07-17 |
| O-002 | Exponential backoff breve (max 3 tentativi) solo per errori retryable | Bilancia affidabilita' e costo: evita retry aggressivi, mantiene prevedibilita' operativa. | Carlo Daniele | 2026-07-17 |
| O-003 | Cutover progressivo per segmento con shadow run limitato iniziale | Mitiga il rischio in produzione: confronti output e metriche prima del rollout totale. | Carlo Daniele | 2026-07-19 |

## Criteri di Accettazione Proposta

- O-001 accettata quando: schema tabella idempotenza definito, policy lock/recovery documentata, test duplicazione run verde.
- O-002 accettata quando: matrice errori retryable/non-retryable definita, policy retry documentata, test retry verde.
- O-003 accettata quando: piano segmenti rollout definito, metriche di successo/fallimento tracciate, regola di rollback documentata.
