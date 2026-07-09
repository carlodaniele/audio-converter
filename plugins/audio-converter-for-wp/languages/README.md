# Localization assets

This directory stores localization files for the plugin text domain:

- Text domain: `audio-converter-for-wp`
- POT template: `audio-converter-for-wp.pot`

## Regenerate POT

From the workspace root, run:

```bash
wp i18n make-pot plugins/audio-converter-for-wp plugins/audio-converter-for-wp/languages/audio-converter-for-wp.pot --slug=audio-converter-for-wp
```

## Add a locale

1. Create `audio-converter-for-wp-<locale>.po` from the POT template (example: `audio-converter-for-wp-it_IT.po`).
2. Compile `.po` to `.mo` (example output: `audio-converter-for-wp-it_IT.mo`).
3. Keep files in this directory.

## JavaScript translations

The editor sidebar uses `wp.i18n.__` and `wp_set_script_translations(...)`, so JS strings are ready for translation packs in future locales.
