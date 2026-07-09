# Audio Converter Program

Questo workspace contiene gli asset operativi per avviare la Fase 1 (Discovery tecnica e baseline riusabile).

## Obiettivo Fase 1

- Estrarre pattern riusabili dai repository di riferimento.
- Identificare gap verso i requisiti commerciali.
- Chiudere decisioni tecniche bloccanti.
- Preparare backlog Sprint 1 senza ambiguita'.

## Struttura

- `docs/phase1/` documentazione operativa della fase.
- `scripts/phase1/` script shell per bootstrap, discovery e validazione.
- `references/repos/` repository di riferimento acquisiti dagli script.
- `reports/phase1/` output prodotti dagli script.

## Quick start

```bash
./scripts/phase1/bootstrap.sh
./scripts/phase1/run-discovery.sh
./scripts/phase1/validate-phase1.sh

./scripts/phase2/bootstrap.sh
./scripts/phase2/validate-contract.sh

./scripts/phase3/bootstrap.sh
./scripts/phase3/validate-phase3.sh
```

## Note

Gli script sono idempotenti: se i repository di riferimento sono gia' presenti, vengono aggiornati con `git pull --ff-only`.
