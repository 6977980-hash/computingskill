# Changelog

Theme versions (`themes/computingskill-live/style.css`). Earlier versions are in the git history and the project's audit notes.

## 1.0.58 (20 Oct 2026) — E5 mobile + accessibility
From the 20 Oct full audit (E5: UX-01, UX-02, A11Y-01..07). Markup, CSS and JS only; no text, numbers or URLs changed.
- UX-01: tables typed into page/post content are wrapped in a `.cs-table-wrap` scroller by a `the_content` filter (`inc/a11y.php`, priority 100), so on phones they scroll sideways instead of being cut off (/psu/, /how-much-ram-do-i-need/, /1080p-vs-1440p-vs-4k/, /cpu-cooler/, /ddr4-vs-ddr5/, /hdd/, /ssd-vs-hdd/ …). Under 600px their cells keep a readable width. Reference tables (D8 `.cs-ref-table`, K1-K4 tables) now wrap text cells: they were 830-1,275px wide and scrolled even on desktop.
- UX-02: mobile menu closes on Escape (focus back on the button) and on a click outside the header; the icon turns into an ×.
- A11Y-01: menu button is at least 44×44px.
- A11Y-02: homepage "Quick answer" label is white on green again (the intro rule had turned it grey).
- A11Y-03: links inside running text (article paragraphs/lists/tables, FAQ answers, disclaimers, calculator sources, "Which page do you need?", hub backlinks, consent banner) are underlined. Nav, cards and buttons unchanged.
- A11Y-04: `main.js` makes every `.cs-table-wrap` that really scrolls focusable (`tabindex="0"`, `role="region"`, label from the caption or the heading before it); rechecked on resize and after calculator results render.
- A11Y-05: light-mode `--cs-gpu` #007f93 → #00768a (4.86:1 on the page background).
- A11Y-06: "Which page do you need?" box is a `<section aria-label="Related pages">` (was an `<aside>` inside `<main>`); empty corner header in `cs_pp_table()` / `cs_k1_table()` gets a screen-reader "Spec"; 404 page has an h2 "Popular tools".
- A11Y-07: consent banner is inserted right after the skip link, so it is the first thing Tab reaches (was 73 Tabs); it still shows fixed at the bottom.

## 1.0.57 (20 Oct 2026) — E4 calculator fixes
From the 20 Oct full audit (E4: FUN-01, FUN-02, FUN-04..08, UX-03). No engine constants changed; PHP and JS engines stay in sync.
- FUN-01: Enter only calculates from an input or select; Enter on a link in a result (combo page, game, methodology) now follows the link.
- FUN-02: Internet Data Usage: a cleared hours field counts as 0, not its default; all four blank shows "Enter the hours per day for at least one activity."
- FUN-04: PSU, PC Power Consumption and PC Cooling: the "Other CPU" watts field shows (and is required) only when "Other CPU" is picked (`showIf` field option). Reserved field heights in `cs_perf_calc_heights()` lowered to match.
- FUN-05: share links made with "Other / not listed" carry `cpu_other=1` / `gpu_other=1`; the link reopens with the closest-part picker and keeps the "isn't in our list" note. An invalid stand-in part is dropped and nothing is auto-calculated.
- FUN-06: `upscaling` / `framegen` flags per game in `data/games-data.php` (checked 20 Oct 2026; missing = not confirmed). FPS Calculator ignores upscaling or frame generation for a picked game that has none (Valorant, League of Legends, Elden Ring, Minecraft Java; frame generation also CS2, BG3, RDR2) and says so above the result.
- FUN-07: numbers always use en-US separators (was the browser locale, "1.234,5"); money always 2 decimals (`fmtMoney()`): Build Cost and PC Power Consumption.
- FUN-08: Storage Converter and SSD Lifespan echo the typed amount with separators ("999,999,999 GB").
- UX-03: PC Upgrade Calculator and Can I Run It get share links ("Copy link to this result"). `cs_share_param_keys()` adds `cpu_other`, `gpu_other`, `ram`, `storageType`, `target` (canonical stays clean); Privacy Policy share sentence names the two tools; `CS_LEGAL_REV` 2026-10-20-e4.

## 1.0.56 (20 Oct 2026) — K4 new keyword content
From the 20 Oct GSC keyword-gap report (K4 in `audit/handoff/K-phases-handoff.md`). New code in `inc/k4-pages.php`; posts are created as WordPress **drafts** for Ali to review and publish.
- New draft posts (`data/blog-drafts/`): "Best AM4 CPU for Gaming (2026): Ryzen 5600 to 5800X3D" (7 AM4 CPUs, 5600 → 5700X3D/5800X3D upgrade table, AM4 vs AM5), "Best CPU for RTX 5060: How Much Processor Is Enough?" (34 CPUs), "Best GPU for Ryzen 7 7800X3D at 1080p, 1440p and 4K" (37 GPUs). Every number generated from `cs_calculate_bottleneck()`, `cs_tl_fps()` and `cs_aeo_psu()`.
- Draft "Best CPU for RTX 5070" rebuilt with the D3/D4 engine and all 34 CPUs (it still had theme 1.0.36 numbers). The sync replaces it only while the draft is untouched.
- Comparison pages (published automatically, like the other 54): RTX 5060 vs RTX 5060 Ti 16GB, RTX 5070 vs RTX 5070 Ti (`cs_pp_vs_pairs()`).
- Combo page Core i5-13600K + RTX 5070 Ti (`cs_get_bottleneck_pairs()`).
- /how-much-vram-do-i-need/: H2 "8GB vs 16GB: RTX 5060 Ti and RX 9060 XT" (maker specs for both memory sizes, estimated FPS 8GB vs 16GB at 1080p-4K high/ultra), 2 FAQ in FAQPage schema, title "How Much VRAM Do I Need? 8GB vs 16GB for 1080p to 4K" (52) and a new meta description. `CS_CALC_PAGES_REV` 2026-10-20-k4 dates the page today.
- `CS_GENERATED_CONTENT_REV` unchanged (no existing combo/game page text changed).

## 1.0.55 (20 Oct 2026) — K3 tool titles and answers
From the 20 Oct GSC keyword-gap report (tools with search volume but no Search Console presence). New code in `inc/k3-pages.php`; sections, tables and links reuse the K1 helpers.
- Title tags (≤60) for 9 pages: Aspect Ratio Calculator (16:9, 21:9), Download Time Calculator (How Long Will It Take?), Monitor Size Calculator (Screen Dimensions in Inches & cm), PPI Calculator (Pixel Density), DPI Calculator (Print vs Mouse DPI), CPU Wattage Calculator, PC Power Consumption Calculator (Wattage & Electricity Cost), PC Part Compatibility Checker, What Is a CPU Bottleneck? Signs & How to Fix It. H1s unchanged.
- Meta descriptions with a number for all 9 (148-160 characters).
- Direct answer under each calculator: 16:9 and 21:9 resolutions table + how to work out 16:9; 25-150GB download times at 50 Mbps-1 Gbps; 24/27/32/34-inch screen sizes; common monitor PPI; largest print at 300/150 DPI; CPU watts at idle/gaming/full load; gaming PC wall draw and monthly cost; CPU-GPU compatibility (PCIe across generations, CPUs without integrated graphics, x8 cards).
- Further sections: best monitor size for 1080p/1440p/4K (PPI table), laptop PPI grid, print DPI vs mouse DPI, cost of gaming time by wall draw, "What has to match in a PC build" table, and a 7-step "How to Fix a CPU Bottleneck" list (the guide's "What to Do About…" H2 is renamed).
- 23 FAQ items in FAQPage schema (2-3 per page) via `cs_k3_extra_faqs()`.
- Monitor size worked example corrected to 23.5 × 13.2 in for a 27-inch screen (it said 23.6 × 13.3; the formula and reference table give 23.5 × 13.2).
- `CS_CALC_PAGES_REV` 2026-10-20-k3 dates the 9 pages today.

## 1.0.54 (20 Oct 2026) — K2 /vram/ page
From the 20 Oct GSC keyword-gap report ("vram", "what is vram", "vram vs ram", "how to check vram", "dedicated vs shared gpu memory"). New code in `inc/k2-pages.php`.
- Title tag "What Is VRAM? VRAM vs RAM & How to Check Your VRAM" (50) and a new meta description; content-index (llms.txt) description updated.
- Opening sentence now starts "VRAM (video RAM, short for video random access memory) is…" (text swap in `inc/calculator-pages.php`).
- Three new H2 sections after "How VRAM Works": "VRAM vs RAM" (comparison table; bandwidth computed from bus width × data rate), "Dedicated vs Shared GPU Memory", "How to Check How Much VRAM You Have" (Task Manager, dxdiag, Windows Settings, GPU-Z, NVIDIA/AMD software, Mac/Linux), with links to How Much VRAM Do I Need?, the GPU VRAM Calculator and What Is RAM?.
- 4 FAQ (VRAM meaning, is VRAM faster than RAM, what uses VRAM, shared GPU memory vs VRAM), in FAQPage schema. The duplicate 1440p extra question no longer shows on /vram/ (its own FAQ answers it; it stays on the GPU VRAM Calculator).
- `CS_CALC_PAGES_REV` 2026-10-20-k2 dates /vram/ today.

## 1.0.53 (20 Oct 2026) — K1 ranking calculators
From the 20 Oct GSC keyword-gap report (calculators already at positions 10-20). New code in `inc/k1-pages.php`.
- Title tags (≤60) and meta descriptions with a number for /storage-calculator/, /ssd-tbw-calculator/ (now "TBW & DWPD"), /ssd-lifespan-calculator/, /resolution-calculator/ and /upload-time-calculator/.
- Storage: "What Fits on 512GB, 1TB, 2TB and 4TB?" table (photos, 1080p and 4K video hours, 100GB games, 90% of the drive used) + 2 FAQ (photos / games in 1TB).
- SSD TBW: "What Does TBW Mean?" answer under the calculator; "Typical TBW by Capacity" table (Samsung 870 EVO, WD_BLACK SN850X, Crucial P3 Plus published ratings); 2 FAQ (good TBW for 1TB, DWPD from TBW).
- SSD Lifespan: "How Long Does an SSD Last?" direct answer, "How to Check SSD Health" (Windows Settings, CrystalDiskInfo, PowerShell, maker apps, Mac/Linux), link to the TBW calculator; FAQ "How long does a 1TB SSD last?".
- Resolution: "How Many Pixels Is 4K?", a Total pixels column in the common resolutions table, and a render scale table (50-85% of 1080p/1440p/4K) with a DLSS/FSR link.
- Upload: H1 "Upload Speed & Time Calculator" (one-time job `CS_K1_REV`, only while the title is unchanged); upload table now at 10/20/50/100 Mbps.
- Extra FAQ (`inc/extra-faq.php`) allows several questions per page, all in FAQPage schema.
- `CS_CALC_PAGES_REV` 2026-10-20-k1 dates the five pages today.

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
