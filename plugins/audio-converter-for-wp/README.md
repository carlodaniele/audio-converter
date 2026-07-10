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

## Requirements

- WordPress 7.0+
- PHP 8.0+
- WordPress AI connectors configured
- User with `edit_posts` capability

## Installation

1. Copy `audio-converter-for-wp` into `wp-content/plugins/`.
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

- Primary: `/wp-json/wp-abilities/v1/audio-converter-for-wp/audio-to-post/run`
- Alternative: `/wp-json/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run`

## i18n

- Text domain: `audio-converter-for-wp`
- POT file: `languages/audio-converter-for-wp.pot`
