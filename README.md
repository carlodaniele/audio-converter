# Audio Converter Program

Repository for an adapter-based audio-to-content system.

## Current architecture target

The project is evolving toward a platform-agnostic core plus adapters:

- `core/` shared business logic contract and orchestration notes.
- `adapters/wordpress/` integration notes for WordPress.
- `adapters/astro/` integration notes for Astro.
- `plugins/audio-converter/` current working WordPress adapter implementation.
- `.github/workflows/` CI automation checks.

Detailed target notes are in:

- `docs/architecture-target.md`

## Existing phase assets

Discovery and historical artifacts are still available:

- `docs/phase1/`
- `scripts/phase1/`
- `scripts/phase2/`
- `scripts/phase3/`
- `references/`
- `reports/`

## Quick checks

Validate agnostic structure locally:

```bash
bash scripts/ci/validate-agnostic-structure.sh
```

Run phase scripts (legacy project phases):

```bash
./scripts/phase1/bootstrap.sh
./scripts/phase1/run-discovery.sh
./scripts/phase1/validate-phase1.sh

./scripts/phase2/bootstrap.sh
./scripts/phase2/validate-contract.sh

./scripts/phase3/bootstrap.sh
./scripts/phase3/validate-phase3.sh
```

## Notes

- WordPress is currently the active production adapter.
- Astro adapter scaffolding is present and will be aligned to the same core contract.
- CI includes an initial structure validation workflow in `.github/workflows/agnostic-structure.yml`.
