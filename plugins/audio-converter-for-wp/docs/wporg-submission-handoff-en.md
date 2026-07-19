# WP.org Submission Handoff (Free v1)

Use this handoff to submit the prepared package and assets to WordPress.org.

## Inputs

- Package zip: `dist/wporg/audio-converter-for-wp-0.1.0.zip`
- Checksum: `dist/wporg/audio-converter-for-wp-0.1.0.zip.sha256`
- Screenshots: `plugins/audio-converter-for-wp/assets/wporg-screenshots/screenshot-1..5.png`
- Icons/Banners: `plugins/audio-converter-for-wp/assets/images/`

## Step 1: Plugin package submission

1. Open the WordPress.org plugin submission form with the plugin owner account.
2. Upload `audio-converter-for-wp-0.1.0.zip`.
3. Complete submission metadata.
4. Save the assigned plugin slug (required for SVN operations).

## Step 2: SVN checkout (after slug assignment)

Replace `<slug>` with the assigned plugin slug.

```bash
svn co https://plugins.svn.wordpress.org/<slug> wporg-<slug>
cd wporg-<slug>
```

## Step 3: Upload visual assets

```bash
mkdir -p assets
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/images/icon-128x128.png assets/
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/images/icon-256x256.png assets/
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/images/banner-772x250.png assets/
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/images/banner-1544x500.png assets/
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/wporg-screenshots/screenshot-1.png assets/
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/wporg-screenshots/screenshot-2.png assets/
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/wporg-screenshots/screenshot-3.png assets/
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/wporg-screenshots/screenshot-4.png assets/
cp /Users/carlo/Documents/audio-converter/plugins/audio-converter-for-wp/assets/wporg-screenshots/screenshot-5.png assets/
svn add assets/* --force
svn status
```

## Step 4: Commit assets

```bash
svn commit -m "Add Free v1 icons, banners, and screenshots"
```

## Step 5: Final manual page review

1. Open the rendered plugin page.
2. Verify icon, banners, and screenshot order.
3. Verify readme sections render correctly.
4. Verify no private/sensitive content appears.

## Step 6: Completion log

Record submission date, plugin slug, and page URL in:

- `plugins/audio-converter-for-wp/docs/wporg-release-readiness-en.md`
