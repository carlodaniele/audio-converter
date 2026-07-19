# WP.org Release Readiness (Free v1)

Use this checklist to finalize and submit the Free v1 package to WordPress.org.

## 1. Metadata and scope

- [x] `Stable tag` set in `readme.txt`.
- [x] Free feature scope aligned in `README.md` and `readme.txt`.
- [x] Confirm decision note for excluded Free v1 formats (`opus`, `webm audio-only`).

Decision note (2026-07-19):

- `opus`: excluded from Free v1 due to current server-side processing limitations.
- `webm` (audio-only): excluded from Free v1 due to current media picker flow constraints.

## 2. Visual assets

- [x] Screenshots `screenshot-1..5.png` present in `assets/wporg-screenshots/`.
- [x] Icon assets present in `assets/images/` (`icon-128x128.png`, `icon-256x256.png`).
- [x] Banner assets present in `assets/images/` (`banner-772x250.png`, `banner-1544x500.png`).

## 3. Runtime validation

- [x] Smoke test passed on real endpoint.
- [x] Retry fault test passed.
- [x] Unauthorized behavior validated.
- [x] Post-cleanup smoke baseline passed.

## 4. Packaging

- [x] Build zip package with:

```bash
cd /Users/carlo/Documents/audio-converter
./scripts/release/build-wporg-package.sh
```

- [x] Confirm generated files:
  - `dist/wporg/audio-converter-0.1.0.zip`
  - `dist/wporg/audio-converter-0.1.0.zip.sha256`

## 5. Submission handoff

- [ ] Upload plugin package to WordPress.org flow.
- [ ] Upload top-level visual assets to WP.org SVN assets folder.
- [ ] Run final manual review on rendered plugin page.

Handoff reference:

- `docs/wporg-submission-handoff-en.md`
