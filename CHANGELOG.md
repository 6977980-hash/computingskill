# Changelog

Theme versions (`themes/computingskill-live/style.css`). Earlier versions are in the git history and the project's audit notes.

## 1.0.50 (20 Oct 2026) — E1 stability
- Fewer database queries on an uncached page (about 415 → 22 on a plain page, 446 → 54 on the FPS Calculator): the quick-answer box only counts combo pages on /bottleneck/, the /hardware/ and /comparisons/ directories only load part URLs on those two pages, and the combo and part URL maps load posts and meta in one go and are shared with the calculators.
- One-time job markers are autoloaded (one query instead of ~15 per request); blog draft sync skips admin-ajax and anonymous requests.
- llms.txt: the daily check saves its time even when the text is unchanged, and the file is written via a temp file + rename.
- /contact/ keeps its no-cache header (the 10-minute browser cache header no longer overrides it).
- Homepage no longer inlines the unused block-library / global-styles CSS.
- Calculator-page and legal date jobs purge only the pages they changed, not the whole LiteSpeed cache.
- `wp-content/db-error.php` (placed once, only if none exists): a database connection failure returns 503 + Retry-After instead of a 500.
