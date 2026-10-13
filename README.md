# ComputingSkill Fixes

Deployer plugin for https://computingskill.com. This repository root **is** the plugin folder. Hostinger Git deploys `main` into:

```
public_html/wp-content/plugins/computingskill-fixes
```

## How it works

- `themes/computingskill-live/` is the site's ComputingSkill theme. **All fixes are made here.**
- On each deploy the plugin copies that theme into `wp-content/themes/computingskill-live` (checked at most once a minute, or via Tools → ComputingSkill Fixes → Sync theme now).
- Because the theme lives in the normal themes folder, fixes stay on the site even if the plugin is deactivated or deleted. Without the plugin, new pushes just stop deploying.
- First switch to **ComputingSkill Live** copies menus/logo/Customizer settings from the previous theme.

## Theme fix layout

- Edit theme templates / `inc/` files directly, or
- `themes/computingskill-live/fixes/NNN-name.php` – one PHP fix per file, loaded in try/catch (a broken fix is logged and skipped). Prefix with `_` to disable.
- `themes/computingskill-live/assets/css/fixes.css`, `assets/js/fixes.js` – CSS/JS fixes.
- Emergency off switch for `fixes/` files: `define( 'CS_DISABLE_FIXES', true );` in `wp-config.php`.
