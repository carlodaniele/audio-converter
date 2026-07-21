# Localization assets

This directory stores localization files for the plugin text domain:

- Text domain: `nomad-pipeline-audio-to-draft`
- POT template: `nomad-pipeline-audio-to-draft.pot`

## Regenerate POT

From the workspace root, run:

```bash
wp i18n make-pot plugins/nomad-pipeline-audio-to-draft plugins/nomad-pipeline-audio-to-draft/languages/nomad-pipeline-audio-to-draft.pot --slug=nomad-pipeline-audio-to-draft
```

## Add a locale

1. Create `nomad-pipeline-audio-to-draft-<locale>.po` from the POT template (example: `nomad-pipeline-audio-to-draft-it_IT.po`).
2. Compile `.po` to `.mo` (example output: `nomad-pipeline-audio-to-draft-it_IT.mo`).
3. Keep files in this directory.

## JavaScript translations

The editor sidebar uses `wp.i18n.__` and `wp_set_script_translations(...)`, so JS strings are ready for translation packs in future locales.
