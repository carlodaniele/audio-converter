# Ability Contract v1 - audio-converter-for-wp/audio-to-post

## Scope

Questa specifica definisce il contratto pubblico v1 per l'ability audio-to-post.
Il contratto e' API-first e vincolante per plugin WordPress e orchestratore.

## Ability

- Name: audio-converter-for-wp/audio-to-post
- Contract version: 1.0.0
- Method: POST (ability run)
- Auth: utente autenticato con permesso edit_posts

## Input Contract (v1)

Campi obbligatori:
- contract_version: stringa semver, deve iniziare con 1.
- external_run_id: stringa unica lato chiamante, chiave idempotenza.
- source: enum [telegram, web, api, manual].
- audio: oggetto con una e una sola modalita' tra media_id, signed_url, base64.

Campi opzionali:
- source_metadata: oggetto libero con metadati sorgente.
- editorial_options:
  - language: string (es. en-US, it-IT)
  - tone: enum [neutral, professional, conversational]
  - target_length: enum [short, medium, long]
  - constraints: array di stringhe
- proper_noun_hints: array di stringhe
- publish_options:
  - status: enum [draft, publish]
  - post_type: string
  - taxonomy_terms: oggetto chiave->array
- media_options:
  - featured_image_id: integer
  - gallery_image_ids: array integer

## Output Contract (v1)

Campi obbligatori:
- contract_version: string, semver 1.x
- run_id: string interno
- status: enum [pending, processing, completed, failed]
- processing_timestamps: object con started_at, completed_at opzionali
- quality_flags: array di string
- debug_reference_id: string

Campi condizionali:
- post_id: integer richiesto quando status=completed
- post_url: string richiesto quando status=completed
- error: object richiesto quando status=failed

## Error Model

Codici canonici:
- invalid_input
- unauthorized
- duplicate_run
- ai_provider_unavailable
- publish_failed
- internal_error

Mappatura minima:
- invalid_input -> HTTP 400
- unauthorized -> HTTP 401/403
- duplicate_run -> HTTP 409
- ai_provider_unavailable -> HTTP 503
- publish_failed -> HTTP 500
- internal_error -> HTTP 500

## Idempotenza

Chiave idempotenza: external_run_id.
Comportamento richiesto:
- Se run completata: ritornare lo stesso risultato senza creare nuovo post.
- Se run in processing: ritornare stato processing e metadati disponibili.
- Se run failed: ritornare errore coerente e permettere retry solo su errori transitori.

## Backward compatibility

- v1.x: additive only.
- Nessuna rimozione o rinomina di campi obbligatori in v1.x.
- Nuovi enum values ammessi solo se non rompono i consumer esistenti.

## Acceptance gates Fase 2

- Schema input valido con esempi pass/fail.
- Schema output valido con success/failure payload.
- Error model unificato e condiviso plugin/orchestratore.
- Contract summary generato automaticamente da script.
