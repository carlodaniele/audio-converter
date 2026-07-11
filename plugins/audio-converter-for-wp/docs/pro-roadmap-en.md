# Pro Roadmap (Planned)

This file tracks planned Pro-only capabilities for future releases.

## Planned feature: non-native audio format support

Goal: allow editors to use audio formats not natively supported by WordPress uploads, such as m4a.

### Why this matters

- Mobile voice recorders often export m4a/aac by default.
- Editorial teams should not need manual conversion before using the plugin.

### Proposed Pro behavior

- Accept additional input formats in the Pro flow (starting with m4a).
- Convert unsupported formats to a supported internal format (mp3 or wav) before AI processing.
- Preserve existing Free behavior unchanged.

### Technical acceptance criteria

- Upload and process at least: m4a, aac, opus, flac (subject to server codec availability).
- Clear error when conversion backend is unavailable.
- Conversion runs with resource limits (max duration, max file size, timeout).
- Temporary files are cleaned up after processing.
- Observability logs include source format and conversion outcome.

### Security and operations notes

- Validate MIME type and extension before conversion.
- Never execute untrusted shell input without strict escaping/whitelisting.
- Keep conversion optional and disabled in Free mode.

### Test plan (for Pro phase)

- Success path: m4a recorded on Android -> converted -> transcribed -> structured output generated.
- Regression path: native mp3/ogg behavior unchanged.
- Failure path: missing converter backend returns actionable error.
- Performance path: long audio near configured limits.
