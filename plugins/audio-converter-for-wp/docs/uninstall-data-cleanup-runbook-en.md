# Uninstall Data Cleanup Runbook (Free v1)

Use this runbook to verify that plugin uninstall removes runtime data created by Audio Converter for WP.

## Scope

This check validates cleanup of:

- Option: `aicb_settings`
- Transients with prefix `aicb_` in `wp_options`
  - `_transient_aicb_%`
  - `_transient_timeout_aicb_%`

## Preconditions

1. Plugin is installed and active.
2. You can run WP-CLI on the target environment.
3. You have permissions to uninstall plugins.

## Step 1 - Create data to be cleaned

1. Open WordPress admin and save plugin settings at least once (Settings > Audio Converter).
2. Run at least one generation request so runtime transients are created.

## Step 2 - Capture baseline before uninstall

Run these commands from the WordPress root:

```bash
TABLE_PREFIX="$(wp db prefix)"
wp option get aicb_settings --format=json
wp db query "SELECT option_name FROM ${TABLE_PREFIX}options WHERE option_name LIKE '_transient_aicb_%' OR option_name LIKE '_transient_timeout_aicb_%' ORDER BY option_name;"
```

Expected: at least one of these queries returns data.

## Step 3 - Uninstall plugin

Choose one path:

- WordPress Admin: Plugins > Installed Plugins > Audio Converter for WP > Delete
- WP-CLI:

```bash
wp plugin uninstall audio-converter-for-wp --deactivate
```

## Step 4 - Verify cleanup after uninstall

Run:

```bash
TABLE_PREFIX="$(wp db prefix)"
wp option get aicb_settings
wp db query "SELECT option_name FROM ${TABLE_PREFIX}options WHERE option_name LIKE '_transient_aicb_%' OR option_name LIKE '_transient_timeout_aicb_%' ORDER BY option_name;"
```

Expected:

- `wp option get aicb_settings` reports option not found.
- SQL query returns zero rows.

## Multisite note

For multisite, uninstall logic iterates all sites and removes `aicb_` transients per site. If you validate manually, run checks against each site table prefix.

## Pass/Fail criteria

- PASS: option removed and no `aicb_` transient rows remain.
- FAIL: any `aicb_` option/transient remains after uninstall.
