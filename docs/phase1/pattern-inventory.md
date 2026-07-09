# Pattern Inventory (Fase 1)

Compilare una riga per ogni pattern osservato nei reference.

| ID | Area | Pattern | Origine | Stato riuso | Note operative |
|---|---|---|---|---|---|
| P-001 | AI Client | Prompt builder chain con controllo supporto | ai-content-builder | Candidate | |
| P-002 | Abilities API | Registrazione ability con schema input/output | ai-content-builder-next | Candidate | |
| P-003 | Security | permission_callback + capability check | ai-content-builder-next | Candidate | |
| P-004 | Output | Normalizzazione strutturata pre-mapping blocchi | ai-content-builder | Candidate | |
| P-005 | Gutenberg | Mapping heading/paragraph/list lato client/server | ai-content-builder | Candidate | |

## Criteri Stato riuso

- `Adopt`: riuso diretto.
- `Adapt`: riuso con modifiche.
- `Reject`: non adatto a requisiti v1.
