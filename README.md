# ComputingSkill Fixes

WordPress plugin with site-specific bug and error fixes for https://computingskill.com.

This repository root **is** the plugin folder. Hostinger Git deploys `main` into:

```
public_html/wp-content/plugins/computingskill-fixes
```

## Layout

- `themes/computingskill-live/` – the site's ComputingSkill theme (v1.0.23), served from this plugin. Activate **ComputingSkill Live** in Appearance → Themes so theme fixes deploy with each push. Menus/logo/Customizer settings are copied from the previous theme on first switch. Do not deactivate the plugin while this theme is active.
- `computingskill-fixes.php` – plugin bootstrap
- `fixes/*.php` – one PHP fix per file, loaded in name order. A broken fix is logged and skipped, not fatal. Prefix with `_` to disable.
- `assets/css/fixes.css`, `assets/js/fixes.js` – front-end CSS/JS fixes
- Tools → ComputingSkill Fixes in wp-admin shows which fixes loaded.

## Emergency off switch

Add to `wp-config.php`:

```php
define( 'CSF_DISABLE_FIXES', true );
```
