# Fase 3 - Plugin modules skeleton

Obiettivo: predisporre la struttura runtime del plugin WordPress con moduli isolati e punti di estensione chiari.

## Moduli

- bootstrap/plugin entrypoint
- abilities + REST controller
- ingestion/audio input resolver
- ai processing
- normalization
- block mapping
- publication
- idempotency + job store
- observability/logging

## Regole

- Nessuna logica Telegram nel plugin.
- Contratto ability v1 come fonte di verita'.
- Idempotenza su external_run_id obbligatoria.
- No publish automatico in caso di output non valido.

## Exit criteria Fase 3 (skeleton)

- File plugin caricabile da WordPress.
- Route REST registrata.
- Callback ability/REST stub con risposta strutturata.
- Moduli separati e includibili.
- Script di validazione fase 3 in verde.
