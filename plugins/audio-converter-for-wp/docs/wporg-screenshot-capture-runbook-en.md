# WP.org Screenshot Capture Runbook (Free Release)

Use this runbook to produce consistent screenshots for the WordPress.org plugin page.

## Output files

Create these files in local order:

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png

## One-time setup

1. Start from a clean WordPress admin session.
2. Set admin language to English.
3. Disable unrelated admin notices if possible.
4. Use browser zoom 100%.
5. Use a fixed viewport for all screenshots (recommended 1440x900).

## Content safety rules

1. Do not expose personal data.
2. Do not expose tokens, API keys, or internal URLs.
3. Use realistic sample post content only.
4. Keep the same visual density across screenshots.

## Capture flow

### Screenshot 1 - Settings page

Path: Settings > Audio Converter

Required visible elements:
- Default language
- Default tone
- Default target length
- Default proper noun hints
- Default insertion mode
- Auto-apply generated title

Framing:
- Include page title and settings table.
- Exclude unrelated plugins/notices when possible.

Save as: screenshot-1.png

### Screenshot 2 - Gutenberg sidebar ready state

Path: Posts > Add New (Block Editor)

Required visible elements:
- Audio Converter sidebar open
- Selected audio file visible
- Generate draft action visible
- Editorial options visible with sane defaults

Framing:
- Sidebar and enough editor area to show context.
- No sensitive post metadata.

Save as: screenshot-2.png

### Screenshot 3 - Generated result in editor

Path: Same post after generation

Required visible elements:
- Generated heading block
- Generated paragraph blocks
- Generated list block (if present)

Framing:
- Keep text readable at plugin page scale.
- Show that content is inserted in the current post editor.

Save as: screenshot-3.png

## Final QA before upload

1. File order and names are exact (screenshot-1/2/3.png).
2. Captions in readme.txt match the images.
3. Resolution and aspect ratio are consistent.
4. No privacy leaks or internal identifiers.
5. UI language is English.

## Optional compression

If PNG files are very large, apply lossless compression before upload.
Do not reduce clarity of labels and key UI text.
