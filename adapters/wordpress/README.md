# WordPress Adapter

This adapter integrates the core logic into WordPress.

Current implementation lives in:

- plugins/audio-converter-for-wp/

Responsibilities:

- Settings page and editor sidebar UX
- Abilities API endpoint exposure
- WordPress media integration
- Mapping WP payloads to core contract

Migration note:

The adapter can later import shared logic from /core to reduce duplication.
