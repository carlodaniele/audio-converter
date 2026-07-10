# Target Architecture

## Goal

Keep business logic platform-agnostic and expose it through adapters.

## Layers

1. core/
2. adapters/wordpress/
3. adapters/astro/
4. .github/workflows/ for automation using the same logic

## Key rules

- Core owns logic; adapters own integration.
- No WordPress-only assumptions in core.
- GitHub Actions must run adapter-agnostic checks and workflows.
- Astro and WordPress should converge on the same normalized contract.
