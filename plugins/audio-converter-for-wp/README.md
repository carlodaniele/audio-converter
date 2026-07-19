# Audio Converter for WP

Audio Converter for WP generates a structured post draft from an audio file directly inside the WordPress block editor.

## Free version scope

- Select audio from Media Library
- Generate draft content blocks from audio
- Configure default language, tone, and target length
- Add proper noun hints
- Choose editor insertion mode: append or replace
- Optionally auto-apply generated title

## Local documentation (no external website required)

- `readme.txt` (WordPress.org format)
- `docs/free-user-guide-en.md`
- `docs/free-faq-en.md`
- `docs/free-user-guide-it.md`
- `docs/free-faq-it.md`
- `docs/free-user-guide-es.md`
- `docs/free-faq-es.md`
- `docs/free-user-guide-pt.md`
- `docs/free-faq-pt.md`
- `docs/free-user-guide-de.md`
- `docs/free-faq-de.md`
- `docs/free-user-guide-fr.md`
- `docs/free-faq-fr.md`
- `docs/free-user-guide-pl.md`
- `docs/free-faq-pl.md`
- `docs/wporg-screenshots-checklist-en.md`
- `docs/wporg-screenshot-capture-runbook-en.md`
- `docs/wporg-visual-assets-runbook-en.md`
- `docs/wporg-release-readiness-en.md`
- `docs/api-endpoint-test-evidence-en.md`
- `docs/uninstall-data-cleanup-runbook-en.md`
- `docs/retry-fault-injection-runbook-en.md`
- `docs/pro-roadmap-en.md`

## Requirements

- WordPress 7.0+
- PHP 8.0+
- At least one WordPress AI connector configured and active
- User with `edit_posts` capability

## Supported audio files (Free v1)

- MP3
- OGG
- WAV
- M4A
- AAC
- FLAC

Important: without an active AI connector, the plugin can be activated but cannot generate drafts from audio.

## Installation

1. In WordPress Admin, go to Plugins > Add New and either upload the plugin ZIP (Upload Plugin) or search for the plugin in the directory.
2. Activate the plugin from Plugins.
3. Open Settings > Audio Converter.
4. Save default editorial values.

## Usage in Gutenberg

1. Open or create a post.
2. Open the Audio Converter sidebar.
3. Select an audio file from Media Library.
4. Configure editorial options.
5. Click Generate draft from audio.

The plugin inserts generated Gutenberg blocks into the current post.

## API endpoint

- Canonical (Abilities API): `/wp-json/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run`

## API endpoint tests (completed)

- [x] Verify canonical endpoint returns successful run output for a valid request.
- [x] Verify response includes expected contract fields (run_id, status, quality_flags, processing_timestamps, debug_reference_id).
- [x] Verify invalid payload returns expected validation error.

Recommended script for replay/idempotency validation:

- `scripts/phase3/test-idempotency-replay.sh`

Recommended script for unauthorized access validation:

- `scripts/phase3/test-unauthorized-access.sh`

Recommended script for retry fault-injection validation:

- `scripts/phase3/test-retry-fault-429.sh`

## i18n

- Text domain: `audio-converter-for-wp`
- POT file: `languages/audio-converter-for-wp.pot`
