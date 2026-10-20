# ComputingSkill Fixes

Deployer plugin for https://computingskill.com. This repository root **is** the plugin folder. Hostinger Git deploys `main` into:

```
public_html/wp-content/plugins/computingskill-fixes
```

## How it works

- `themes/computingskill-live/` is the site's ComputingSkill theme. **All fixes are made here.**
- On each deploy the plugin copies that theme into `wp-content/themes/computingskill-live` (checked at most once a minute, or via Tools → ComputingSkill Fixes → Sync theme now). The copy goes into a hidden staging folder first, is checked file by file, and is swapped in with a folder rename, so the site never runs half old, half new. A failed copy leaves the live theme as it was. The check runs before WordPress loads the theme and also compares the live copy with the bundled one, so a broken, partial or hand-edited live copy is replaced within a minute (edit the theme in this repo, not in wp-admin).
- This folder has its own `.htaccess` that denies web access (nothing here is served to browsers).
- Because the theme lives in the normal themes folder, fixes stay on the site even if the plugin is deactivated or deleted. Without the plugin, new pushes just stop deploying.
- First switch to **ComputingSkill Live** copies menus/logo/Customizer settings from the previous theme.

## Theme fix layout

- Edit theme templates / `inc/` files directly, or
- `themes/computingskill-live/fixes/NNN-name.php` – one PHP fix per file, loaded in try/catch (a fix that throws is logged and skipped; a "Cannot redeclare" fatal can't be caught, so prefix function names with the fix number). Prefix with `_` to disable.
- `themes/computingskill-live/assets/css/fixes.css`, `assets/js/fixes.js` – CSS/JS fixes.
- Emergency off switch for `fixes/` files: `define( 'CS_DISABLE_FIXES', true );` in `wp-config.php`.
