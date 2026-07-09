# Error Model - audio-to-post v1

## Canonical error object

{
  "code": "invalid_input",
  "message": "Human readable message",
  "retryable": false,
  "details": {}
}

## Codes

1. invalid_input
- Causa: payload non conforme a schema o campi incompatibili.
- Retryable: false
- HTTP: 400

2. unauthorized
- Causa: autenticazione assente o permessi insufficienti.
- Retryable: false
- HTTP: 401 o 403

3. duplicate_run
- Causa: external_run_id gia' visto con stato non compatibile a nuova esecuzione.
- Retryable: false
- HTTP: 409

4. ai_provider_unavailable
- Causa: provider non configurato/non disponibile/timeouts transitori.
- Retryable: true
- HTTP: 503

5. publish_failed
- Causa: errore nella creazione/aggiornamento post WordPress.
- Retryable: dipende dal dettaglio; default false
- HTTP: 500

6. internal_error
- Causa: errore non classificato.
- Retryable: false
- HTTP: 500

## Mapping rule

Il layer orchestratore deve preservare code, retryable e message. Se necessario puo' aggiungere contesto in details senza alterare code.
