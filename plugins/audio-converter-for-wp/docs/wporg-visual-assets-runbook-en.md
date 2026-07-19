# WP.org Visual Assets Runbook (Free Release)

Use this runbook to produce the WordPress.org visual assets outside screenshots.

## Decision (2026-07-19)

- Concept generation: Ideogram
- Final layout and export: Figma
- Final polish/compression: Canva or TinyPNG lossless

## Style direction

- Direction: Product UI-first with clean editorial tone
- Palette: neutral light background, deep blue accents, high text contrast
- Typography: simple geometric sans-serif, readable at small sizes
- Message focus: audio to structured draft in Gutenberg

## Required WP.org assets

1. `icon-128x128.png`
2. `icon-256x256.png`
3. `banner-772x250.png`
4. `banner-1544x500.png`

Optional:

1. `assets.svg` source file for future edits
2. `icon-square-source.png` (master square source)
3. `banner-source.png` (master wide source)

## Asset content rules

- Do not include fake customer logos, private brands, or provider names.
- Do not show API keys, tokens, URLs, or account identifiers.
- Keep plugin name readable: Audio Converter for WP.
- Keep text minimal on icon variants.
- Ensure contrast remains readable on both light and dark wp.org backgrounds.

## Suggested composition

### Icon

- Main symbol: waveform or microphone + document/page motif.
- Secondary hint: subtle block/grid motif to suggest Gutenberg output.
- Keep details large enough for 128x128 rendering.

### Banner

- Left: strong title and short value proposition.
- Right: product UI crop (sidebar + generated blocks), no sensitive data.
- Keep safe margins to avoid clipping in wp.org layout.

## Copy options for banner

Use one short headline:

1. Turn Audio Notes into Structured Drafts
2. Audio to Gutenberg Draft in Seconds
3. Generate Post Drafts from Audio in WordPress

Optional subtitle:

- Free plugin for editorial teams using the block editor.

## Export checklist

1. Export exact file names and dimensions.
2. Verify text readability at 100% and 75% zoom.
3. Run lossless compression only.
4. Save final files in a staging folder before SVN upload.

## QA checklist

1. Icon is recognizable at 128x128.
2. Banner headline is readable at standard wp.org page width.
3. No visual overlap with wp.org UI chrome.
4. No legal or privacy issues in imagery/text.
5. Visual style matches screenshots and plugin tone.

## Delivery path

For WP.org SVN top-level assets folder:

- `assets/icon-128x128.png`
- `assets/icon-256x256.png`
- `assets/banner-772x250.png`
- `assets/banner-1544x500.png`
