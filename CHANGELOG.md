# Changelog

Theme versions (`themes/computingskill-live/style.css`). Earlier versions are in the git history and the project's audit notes.

## 1.0.52 (20 Oct 2026) — E3 technical SEO + trust
- Part (`/hardware/{part}/`) and comparison (`/comparisons/{a}-vs-{b}/`) pages show "By Ali Ahmad · Last updated" and carry a WebPage node with author, publisher, dates, description and the page's share card as `primaryImageOfPage` (ONP-01).
- Comparison title tags are at most 60 characters (shorter endings for long part names; the H1 keeps the full title); DLSS guide title 59 characters (SEO-03).
- `/comparisons/{b}-vs-{a}/` 301s to the existing `{a}-vs-{b}` page (SEO-06).
- Can I Run It is linked from the homepage, the /calculators/ and /games/ hubs; the low GPU usage and DLSS/FSR guides are on /guides/ and in a new "Which Page Do You Need?" box on the FPS Calculator, Can I Run It, How to Improve FPS and both guides; the bottleneck explainers link them too (SEO-02).
- Can I Run It is a WebApplication (og:type website), like the other calculators (SEO-04).
- Homepage FAQ has FAQPage schema (ONP-04).
- CPU and GPU Performance Calculator intro and FAQ answers, and their llms.txt lines, describe the score-based method; /computer-tools/ added to llms.txt (ONP-02). Hub quick answers name the new pages.
- Dates (ONP-03): /calculators/, /guides/, /hardware/, /comparisons/ and the CPU/GPU Performance pages are dated today; 5 hubs and 16 tool pages that changed in D8 move from September to 18 Oct 2026. New part, comparison and combo pages now also move their hub's date.
- Terms of Use no longer say "specs-based estimate"; Privacy Policy explains the contact form's 10-minute hashed-IP spam counter (CS_LEGAL_REV 2026-10-20-e3) (FUN-03).
- Comments feed link removed from `<head>`; comment feed URLs 301 to the main feed / the post. A real `/favicon.ico` is written once from the site icon (the host served a static 404). Post category label only links categories that aren't noindexed as thin (SEO-07).

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
