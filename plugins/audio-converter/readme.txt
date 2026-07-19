=== Audio Converter ===
Contributors: carlo
Tags: ai, audio, content, editor, gutenberg
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate a structured post draft from an audio file directly in the WordPress block editor.

== Description ==

Audio Converter helps editors turn audio notes into post drafts.

Draft generation requires at least one configured WordPress AI connector. If no connector is available, the plugin can stay active but generation requests will return provider errors.

Free version includes:

* Audio selection from Media Library
* Supported file types: MP3, OGG, WAV, M4A, AAC, FLAC
* Draft generation from audio (AI pipeline)
* Language, tone, and target length defaults
* Proper noun hints
* Insert mode: append or replace
* Auto-apply generated title
* Editor flow updates the current post

How it works:

1. Open a post in the block editor.
2. Open the Audio Converter sidebar.
3. Select an audio file from Media Library.
4. Click "Generate draft from audio".

The plugin generates content blocks and inserts them into the current post.

No external website or tutorial is required: local documentation is included in the plugin package.

External services and data handling:

* To generate a draft, the plugin sends the selected audio payload and selected editorial options to the configured WordPress AI connector.
* Data processing, retention, and model behavior are managed by the active connector/provider, not by this plugin.
* This plugin does not add its own analytics tracker or advertising pixel.
* Site owners should review the privacy policy and terms of the selected AI connector/provider before production use.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/ or install from Plugins.
2. Activate "Audio Converter".
3. Go to Settings > Audio Converter and save your defaults.
4. Open any post in the block editor and use the Audio Converter sidebar.

== Frequently Asked Questions ==

= Does it create a new post? =

In the editor flow, it updates the current post.


= Do I need a separate website with tutorials? =

No. Use the included docs in the plugin package:

* docs/free-user-guide-en.md
* docs/free-faq-en.md
* docs/free-user-guide-it.md
* docs/free-faq-it.md
* docs/free-user-guide-es.md
* docs/free-faq-es.md
* docs/free-user-guide-pt.md
* docs/free-faq-pt.md
* docs/free-user-guide-de.md
* docs/free-faq-de.md
* docs/free-user-guide-fr.md
* docs/free-faq-fr.md
* docs/free-user-guide-pl.md
* docs/free-faq-pl.md

= Why do I see AI provider errors? =

Check that WordPress AI connectors are correctly configured and available.

== Screenshots ==

1. Settings page with Free editorial defaults (language, tone, target length, insertion mode).
2. Gutenberg sidebar with selected audio and Generate draft action.
3. Current post updated with generated Gutenberg blocks (heading, paragraphs, list).
4. Media Library modal used to pick the audio file before generation.
5. Error message shown in the sidebar when no AI connector is configured/available.

== Changelog ==

= 0.1.0 =
* Initial public Free release.
* Audio-to-draft pipeline with Gutenberg integration.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
