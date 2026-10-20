# Changelog

Theme versions (`themes/computingskill-live/style.css`). Earlier versions are in the git history and the project's audit notes.

## 1.0.51 (20 Oct 2026) — E2 deploy + security
Deployer plugin 1.3.0:
- The theme is copied into a hidden staging folder, checked file by file, and swapped in with a folder rename, so a deploy never leaves the site half old, half new; a failed copy leaves the live theme untouched. If the folder can't be renamed it falls back to temp file + rename per file, `functions.php` last.
- If the plugin folder changes during the copy (git checkout still running), nothing is swapped and no "done" hash is saved, so the next minute retries.
- The check runs on `plugins_loaded`, before WordPress loads the theme, and also re-syncs when the live copy no longer matches the bundled one, so a broken or partial live theme repairs itself within a minute (hand edits to the live theme are replaced too).
- New `.htaccess` in the plugin folder denies web access (README and the bundled theme copy were readable).
Theme:
- XML-RPC fully off: no methods, no RSD link, no X-Pingback, and `xmlrpc.php` denied at the server.
- `inc/hardening.php` writes a marked block into the root `.htaccess` (tested by loading the homepage and stylesheet; restored at once on anything but 200): XML-RPC deny, readme/license/changelog deny, HSTS + nosniff + Referrer-Policy + Permissions-Policy on every response (login, admin, REST, static files), 1-year browser cache for CSS/JS/fonts/images. PHP stops sending those four headers once the server sends them.
- CSP adds `object-src 'none'; base-uri 'self'`. Feed generator tag removed. Comments and pings off (the theme has no comments.php).
- Contact form: 3 sends per visitor per 10 minutes, 20 per hour site-wide, minimum 3 seconds fill time.
- `defined( 'ABSPATH' )` guard on every root template and page template (direct requests returned 500 with partial HTML).
- Cleanup: `CS_THEME_VERSION` reads style.css, dropped the unregistered comment-reply handle, fixed stale comments.

## 1.0.50 (20 Oct 2026) — E1 stability
- Fewer database queries on an uncached page (about 415 → 22 on a plain page, 446 → 54 on the FPS Calculator): the quick-answer box only counts combo pages on /bottleneck/, the /hardware/ and /comparisons/ directories only load part URLs on those two pages, and the combo and part URL maps load posts and meta in one go and are shared with the calculators.
- One-time job markers are autoloaded (one query instead of ~15 per request); blog draft sync skips admin-ajax and anonymous requests.
- llms.txt: the daily check saves its time even when the text is unchanged, and the file is written via a temp file + rename.
- /contact/ keeps its no-cache header (the 10-minute browser cache header no longer overrides it).
- Homepage no longer inlines the unused block-library / global-styles CSS.
- Calculator-page and legal date jobs purge only the pages they changed, not the whole LiteSpeed cache.
- `wp-content/db-error.php` (placed once, only if none exists): a database connection failure returns 503 + Retry-After instead of a 500.
