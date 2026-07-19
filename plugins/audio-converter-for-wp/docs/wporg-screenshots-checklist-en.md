# WP.org Screenshots Checklist (Free Release)

Use this checklist before capturing and uploading screenshots for the plugin page.

## Required screenshots (minimum set)

1. Settings page (Free defaults only)
2. Gutenberg sidebar (audio selected, ready to generate)
3. Editor result (generated blocks inserted in current post)
4. Media Library modal during audio selection
5. Error message state in sidebar (provider/runtime example)

## Capture setup

- Use English UI for screenshots used in WordPress.org listing.
- Ensure no Pro labels, badges, or upsell sections are visible.
- Use realistic but non-sensitive content.
- Keep WordPress admin clean (no unrelated notices if possible).
- Use consistent browser zoom and resolution.

## Recommended resolution and naming

- PNG format preferred.
- Suggested width: 1200 to 1600 px.
- Keep the same aspect ratio for all screenshots.
- Name files in upload order:
  - screenshot-1.png
  - screenshot-2.png
  - screenshot-3.png
  - screenshot-4.png
  - screenshot-5.png

## What each screenshot should show

### Screenshot 1 - Settings page

- Location: Settings > Audio Converter
- Show only Free settings:
  - Default language
  - Default tone
  - Default target length
  - Proper noun hints
  - Default insertion mode
  - Auto-apply generated title

### Screenshot 2 - Gutenberg sidebar

- Open a post in Block Editor.
- Open Audio Converter sidebar.
- Show selected audio file name and Generate button.
- Optional: show language/tone/length fields populated.

### Screenshot 3 - Generated result in editor

- Show post content after generation.
- Include heading + paragraphs + list blocks to demonstrate output quality.
- Keep content readable at normal plugin page scale.

### Screenshot 4 - Media Library audio selection

- Open the Media Library picker from the plugin sidebar.
- Show at least one audio item with clear file name and type.
- Keep personal media names hidden if needed.

### Screenshot 5 - Error message state

- Capture the sidebar error shown when no AI connector is configured/available.
- Keep the message readable and actionable.
- Avoid exposing secrets, tokens, account IDs, or private URLs.

## Final verification before upload

- Screenshot order matches descriptions in readme.txt.
- No personal data, tokens, or internal URLs visible.
- No spelling errors in UI labels.
- Captions in readme.txt match actual images.

## Release validation status

Use this section to track what is done and what is still pending before WP.org submission.

- [x] Screenshot set captured (screenshot-1/2/3/4/5.png).
- [x] Audio format test completed: mp3.
- [x] Audio format test completed: ogg.
- [x] Audio format test completed: wav.
- [x] Audio format test completed: m4a.
- [x] Audio format test completed: flac.
- [x] Audio format test completed: aac.
- [ ] Out of Free v1 support for now: opus (WP server processing issue).
- [ ] Out of Free v1 support for now: webm audio-only (not selectable in current media picker flow).

## WP.org visual assets (outside screenshots)

- [x] Choose the image design service/workflow for WP.org visual assets.
- [x] Define final style direction (clean editorial, modern SaaS, or product UI-first).
- [x] Generate icon and banner candidates.
- [x] Validate branding and readability at small sizes.
- [x] Select final assets for SVN top-level assets folder.

Decision recorded in:

- `docs/wporg-visual-assets-runbook-en.md`

Selected workflow:

- Ideogram for concept drafts
- Figma for final layout/export
- Canva or TinyPNG lossless for final optimization

Selected style direction:

- Product UI-first with clean editorial tone

Final assets prepared:

- `assets/images/icon-128x128.png` (128x128)
- `assets/images/icon-256x256.png` (256x256)
- `assets/images/banner-772x250.png` (772x250)
- `assets/images/banner-1544x500.png` (1544x500)

Suggested services to evaluate:

- Midjourney (strong concept quality)
- Ideogram (good control on text inside images)
- Leonardo AI (many presets and fast iterations)
- Canva/Figma (final cleanup and precise export sizing)
