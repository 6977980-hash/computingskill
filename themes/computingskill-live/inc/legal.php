<?php
/**
 * Legal and trust fixes (deep audit phase D1, Oct 2026).
 *
 * - Privacy Policy: names Google Analytics, the consent banner and the
 *   cookies that really exist, plus GDPR and US state (CCPA) rights. The
 *   page text lives in the database, so the changed sections are swapped in
 *   with the_content, like cs_methodology_engine_text() does (6.2).
 * - Terms of Use and Editorial Policy pages, created once and filled from
 *   here through [cs_policy_page] (6.4, 6.8).
 * - Trademark notice on the Disclaimer (and in footer.php) (6.7).
 * - Security headers (6.5), and the public user list closed (6.6).
 *
 * The contact form token fix (6.3) is in inc/contact-form.php and the GA
 * consent banner (6.1) in inc/analytics.php.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bump when the policy text below changes: the Privacy Policy and
 * Disclaimer get their modified date set once, and the page cache is
 * purged once so the new footer and headers reach cached pages.
 */
define( 'CS_LEGAL_REV', '2026-10-20-e4' );
define( 'CS_PRIVACY_UPDATED', 'October 20, 2026' );

/* ------------------------------------------------------------------ */
/* Privacy Policy (6.2)                                                */
/* ------------------------------------------------------------------ */

function cs_privacy_cookies_html() {
	$privacy = 'https://policies.google.com/privacy';
	$partner = 'https://policies.google.com/technologies/partner-sites';
	$optout  = 'https://tools.google.com/dlpage/gaoptout';
	ob_start();
	?>
<h2 id="cookies"><?php esc_html_e( 'Cookies', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'This site sets cookies in only these cases:', 'computingskill' ); ?></p>
<ul>
<li><strong>cs_consent.</strong> <?php esc_html_e( 'Remembers whether you accepted or rejected analytics in the cookie banner, so it doesn’t ask again on every page. It holds only the word “granted” or “denied” and lasts 180 days.', 'computingskill' ); ?></li>
<li><strong>_ga <?php esc_html_e( 'and', 'computingskill' ); ?> _ga_&lt;ID&gt;.</strong> <?php esc_html_e( 'Google Analytics cookies. They are set only after you press “Accept analytics” in the cookie banner, and last up to 2 years.', 'computingskill' ); ?></li>
<li><strong><?php esc_html_e( 'WordPress login cookies.', 'computingskill' ); ?></strong> <?php esc_html_e( 'Set only for the site’s own editors when they sign in. Visitors never get them.', 'computingskill' ); ?></li>
</ul>
<p><?php esc_html_e( 'If you reject analytics, or never answer the banner, Google Analytics is not loaded and its cookies are not set. You can change your choice at any time with the “Cookie settings” link at the bottom of every page. Rejecting after you had accepted also deletes the Google Analytics cookies this site set.', 'computingskill' ); ?></p>
<h2 id="analytics"><?php esc_html_e( 'Analytics', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'We use Google Analytics 4, a service provided by Google, to count visits and see which pages and calculators are used, so we know what to fix and improve. It runs only if you accept it in the cookie banner. Until then, Google Consent Mode keeps every storage type set to “denied” and the Google Analytics script is not downloaded at all.', 'computingskill' ); ?></p>
<p><?php esc_html_e( 'When you accept, Google receives details such as the pages you view, the page that sent you here, your device, browser and screen type, an approximate location worked out from your IP address, and a random identifier stored in the _ga cookie. Google states that Google Analytics 4 does not log or store IP addresses. We have turned off Google Signals and advertising personalisation for this site, and we don’t use analytics data to show you ads. Google keeps the data for the retention period set in our Analytics account.', 'computingskill' ); ?></p>
<p><?php esc_html_e( 'If your browser sends a Global Privacy Control (GPC) signal and you haven’t made a choice, we treat it as “Reject”.', 'computingskill' ); ?></p>
<p>
	<?php esc_html_e( 'More from Google:', 'computingskill' ); ?>
	<a href="<?php echo esc_url( $privacy ); ?>" rel="noopener"><?php esc_html_e( 'Google Privacy Policy', 'computingskill' ); ?></a>,
	<a href="<?php echo esc_url( $partner ); ?>" rel="noopener"><?php esc_html_e( 'how Google uses data from sites that use its services', 'computingskill' ); ?></a>,
	<?php esc_html_e( 'and the', 'computingskill' ); ?>
	<a href="<?php echo esc_url( $optout ); ?>" rel="noopener"><?php esc_html_e( 'Google Analytics opt-out browser add-on', 'computingskill' ); ?></a>.
</p>
<h2 id="advertising"><?php esc_html_e( 'Advertising', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'ComputingSkill.com shows no ads and has no affiliate links. If that ever changes, this policy will name the provider before it goes live.', 'computingskill' ); ?></p>
	<?php
	return ob_get_clean();
}

function cs_privacy_rights_html() {
	ob_start();
	?>
<h2 id="your-rights"><?php esc_html_e( 'Your Rights', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'If you’d like to know what information we hold about you (typically limited to a past contact form submission), have it corrected, or have it deleted, you can ask through the Contact page. We’ll respond to reasonable requests to the extent we’re able, given the limited information we actually retain. For analytics, use “Cookie settings” at the bottom of any page.', 'computingskill' ); ?></p>
<h3><?php esc_html_e( 'Visitors in the EEA, UK and Switzerland', 'computingskill' ); ?></h3>
<p><?php esc_html_e( 'We use Google Analytics only with your consent, which you can withdraw at any time through “Cookie settings”. We keep server logs for the site’s legitimate interest in security and troubleshooting, and we use contact form details to answer the message you sent. You have the right to access, correct, delete, restrict or object to the use of your information, to receive a copy of it, and to complain to your local data protection authority.', 'computingskill' ); ?></p>
<h3 id="do-not-sell"><?php esc_html_e( 'California and other US states: Do Not Sell or Share', 'computingskill' ); ?></h3>
<p><?php esc_html_e( 'We do not sell your personal information, and we do not share it for cross-context behavioral advertising, as the California Consumer Privacy Act (CCPA, as amended by the CPRA) defines those terms. Google Analytics is used only to measure this site, with advertising features off. You can still turn analytics off with “Cookie settings” or a GPC signal, and you can ask to know, correct or delete your information through the Contact page. We won’t treat you differently for using any of these rights.', 'computingskill' ); ?></p>
	<?php
	return ob_get_clean();
}

function cs_privacy_policy_text( $content ) {
	if ( ! is_page( 'privacy-policy' ) || ! in_the_loop() || ! is_main_query() || false !== strpos( $content, 'id="cookies"' ) ) {
		return $content;
	}

	// Replaces one whole section, from its heading up to the next one.
	$swap = function ( $content, $from, $to, $html ) {
		$pattern = '#<h2[^>]*>\s*' . preg_quote( $from, '#' ) . '\s*</h2>.*?(?=<h2[^>]*>\s*' . preg_quote( $to, '#' ) . '\s*</h2>)#s';
		$new     = preg_replace( $pattern, str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $html ), $content, 1 );
		return null === $new ? $content : $new;
	};

	$content = $swap( $content, 'Cookies', 'Third-Party Services', cs_privacy_cookies_html() );
	$content = $swap( $content, 'Your Rights', 'Changes to This Policy', cs_privacy_rights_html() );

	$content = preg_replace(
		'#Last updated:\s*[A-Z][a-z]+ \d{1,2}, \d{4}#',
		esc_html( sprintf( __( 'Last updated: %s', 'computingskill' ), CS_PRIVACY_UPDATED ) ),
		$content,
		1
	);

	// Name the site's operator once, after the opening paragraph.
	$author  = cs_site_author();
	$who     = '<p class="cs-privacy-who">' . sprintf(
		/* translators: %s: author name linked to About Us */
		esc_html__( 'ComputingSkill.com is run by %s, who is responsible for the information described here.', 'computingskill' ),
		'<a href="' . esc_url( $author['url'] ) . '">' . esc_html( $author['name'] ) . '</a>'
	) . '</p>';
	$content = preg_replace( '#(<h2[^>]*>\s*Information We Collect\s*</h2>)#', str_replace( '$', '\\$', $who ) . '$1', $content, 1 );

	// Add analytics to the "Information We Collect" list (its first </ul>).
	$item = '<li><strong>' . esc_html__( 'Analytics data, only with your consent.', 'computingskill' ) . '</strong> ' . esc_html__( 'If you accept analytics in the cookie banner, Google Analytics records how pages are used. See Analytics below.', 'computingskill' ) . '</li>';
	// E3: the contact form's spam limit (E2, inc/contact-form.php).
	$item .= "\n" . '<li><strong>' . esc_html__( 'A short-lived spam counter for the contact form.', 'computingskill' ) . '</strong> ' . esc_html__( 'To limit each visitor to 3 messages in 10 minutes, the site keeps a one-way, salted hash of your IP address with a count, never the address itself. It can’t be turned back into your IP address and is deleted automatically 10 minutes after your last message.', 'computingskill' ) . '</li>';
	$pos  = strpos( $content, '</ul>' );
	if ( false !== $pos ) {
		$content = substr_replace( $content, $item . "\n", $pos, 0 );
	}

	// The FAQ answer that said "this policy is written to cover both cases".
	$content = preg_replace(
		'#(Does this site currently run ads or third-party analytics\?</h3>\s*<div class="cs-faq-answer">).*?(</div>)#s',
		'$1<p>' . esc_html__( 'There are no ads. We use Google Analytics, and only after you press “Accept analytics” in the cookie banner. See the Analytics section above.', 'computingskill' ) . '</p>' . "\n" . '$2',
		$content,
		1
	);

	return $content;
}
add_filter( 'the_content', 'cs_privacy_policy_text', 21 );

/* ------------------------------------------------------------------ */
/* Trademark notice on the Disclaimer (6.7)                            */
/* ------------------------------------------------------------------ */

function cs_trademark_sentence() {
	return __( 'NVIDIA, GeForce, RTX, AMD, Radeon, Ryzen, Intel and Intel Core are trademarks of their respective owners. Computing Skill is independent and is not affiliated with, sponsored by or endorsed by any of them.', 'computingskill' );
}

function cs_disclaimer_trademarks( $content ) {
	if ( ! is_page( 'disclaimer' ) || ! in_the_loop() || ! is_main_query() || false !== strpos( $content, 'id="trademarks"' ) ) {
		return $content;
	}
	$html = '<h2 id="trademarks">' . esc_html__( 'Trademarks', 'computingskill' ) . '</h2><p>' . esc_html( cs_trademark_sentence() ) . ' ' . esc_html__( 'Product and brand names are used only to identify the hardware and games our tools and guides cover.', 'computingskill' ) . '</p>' . "\n";
	$pos  = strpos( $content, '<div class="cs-related">' );
	return false === $pos ? $content . $html : substr_replace( $content, $html, $pos, 0 );
}
add_filter( 'the_content', 'cs_disclaimer_trademarks', 21 );

/* ------------------------------------------------------------------ */
/* Terms of Use and Editorial Policy pages (6.4, 6.8)                  */
/* ------------------------------------------------------------------ */

function cs_policy_pages() {
	return array(
		'terms-of-use'     => __( 'Terms of Use', 'computingskill' ),
		'editorial-policy' => __( 'Editorial Policy', 'computingskill' ),
	);
}

/**
 * Creates each page once, published, holding only its shortcode, so the
 * text stays in the theme. A page that already exists (or was deleted
 * after being created) is never touched.
 */
function cs_policy_pages_create() {
	if ( wp_installing() ) {
		return;
	}
	$done = (array) get_option( 'cs_policy_pages_created', array() );
	$todo = array_diff( array_keys( cs_policy_pages() ), $done );
	if ( empty( $todo ) ) {
		return;
	}
	if ( ! add_option( 'cs_policy_pages_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_policy_pages_lock' ) > 300 ) {
			delete_option( 'cs_policy_pages_lock' );
		}
		return;
	}
	$privacy = get_page_by_path( 'privacy-policy' );
	$author  = $privacy ? (int) $privacy->post_author : 0;
	$titles  = cs_policy_pages();
	foreach ( $todo as $slug ) {
		if ( ! get_page_by_path( $slug ) ) {
			$id = wp_insert_post( array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_name'      => $slug,
				'post_title'     => $titles[ $slug ],
				'post_author'    => $author,
				'post_content'   => '[cs_policy_page name="' . $slug . '"]',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			), true );
			if ( is_wp_error( $id ) ) {
				continue;
			}
		}
		$done[] = $slug;
	}
	update_option( 'cs_policy_pages_created', array_values( array_unique( $done ) ), true );
	delete_option( 'cs_policy_pages_lock' );
}
add_action( 'init', 'cs_policy_pages_create', 30 );

function cs_policy_page_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'name' => '' ), $atts, 'cs_policy_page' );
	if ( 'terms-of-use' === $atts['name'] ) {
		return cs_terms_of_use_html();
	}
	if ( 'editorial-policy' === $atts['name'] ) {
		return cs_editorial_policy_html();
	}
	return '';
}
add_shortcode( 'cs_policy_page', 'cs_policy_page_shortcode' );

function cs_policy_link( $slug, $text ) {
	return '<a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . esc_html( $text ) . '</a>';
}

function cs_terms_of_use_html() {
	ob_start();
	?>
<p><?php esc_html_e( 'These Terms of Use cover how you may use ComputingSkill.com (“this site,” “we,” “us”). By using the site you agree to them. If you don’t agree, please don’t use the site.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'Free to use, no account', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'The calculators, tools and guides are free and need no account. You can use them for your own builds, upgrades and purchases, and you can share links to any page or calculator result.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'Estimates, not guarantees', 'computingskill' ); ?></h2>
<p><?php
	/* translators: %s: link to the Disclaimer page */
	// E3 (FUN-03): "specs-based" no longer described how the scores are made (D3).
	printf( esc_html__( 'Every result on this site is an estimate built from manufacturer specifications, our own gaming-performance scores (placed from published review averages) and documented formulas, not a measurement or a promise of how a real PC will perform. The %s explains these limits in detail and is part of these terms. Check manufacturer specifications and current reviews before you spend money.', 'computingskill' ), cs_policy_link( 'disclaimer', __( 'Disclaimer', 'computingskill' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- link built from escaped parts.
?></p>
<h2><?php esc_html_e( 'Acceptable use', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'Please don’t:', 'computingskill' ); ?></p>
<ul>
<li><?php esc_html_e( 'attack, probe or try to break into the site or its server;', 'computingskill' ); ?></li>
<li><?php esc_html_e( 'send automated or bulk requests that slow the site down for other visitors;', 'computingskill' ); ?></li>
<li><?php esc_html_e( 'use the contact form for spam, advertising or abuse;', 'computingskill' ); ?></li>
<li><?php esc_html_e( 'copy whole pages, the calculators or the hardware data and publish them as your own.', 'computingskill' ); ?></li>
</ul>
<h2><?php esc_html_e( 'Our content', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'The text, calculators, scores and design of this site belong to Computing Skill. You’re welcome to quote a short passage or a single result if you link back to the page it came from. For anything more, ask first through the Contact page.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'Trademarks', 'computingskill' ); ?></h2>
<p><?php echo esc_html( cs_trademark_sentence() ); ?></p>
<h2><?php esc_html_e( 'Links to other sites', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'Links to manufacturer pages and other sites are there for convenience. We don’t control those sites and aren’t responsible for their content.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'No warranty and limits of liability', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'The site is provided “as is” and “as available”, without warranties of any kind. To the extent the law allows, Computing Skill is not liable for any loss or damage that comes from using the site or relying on its results, including the cost of hardware bought on the basis of an estimate. Nothing in these terms limits rights that the law says cannot be limited.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'Your privacy', 'computingskill' ); ?></h2>
<p><?php
	/* translators: %s: link to the Privacy Policy */
	printf( esc_html__( 'How we handle contact messages, server logs, cookies and analytics is explained in the %s.', 'computingskill' ), cs_policy_link( 'privacy-policy', __( 'Privacy Policy', 'computingskill' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
?></p>
<h2><?php esc_html_e( 'Changes', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'We may update these terms. The date at the top of this page shows the latest change, and using the site after a change means you accept the new version.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'Contact', 'computingskill' ); ?></h2>
<p><?php
	/* translators: %s: link to the Contact page */
	printf( esc_html__( 'Questions about these terms can be sent through the %s.', 'computingskill' ), cs_policy_link( 'contact', __( 'Contact page', 'computingskill' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
?></p>
	<?php
	return ob_get_clean();
}

function cs_editorial_policy_html() {
	$author = cs_site_author();
	ob_start();
	?>
<p><?php esc_html_e( 'This page explains how the calculators, guides and comparison pages on ComputingSkill.com are written, checked and corrected.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'Who writes the site', 'computingskill' ); ?></h2>
<p><?php
	/* translators: %s: author name linked to About Us */
	printf( esc_html__( 'Computing Skill is an independent site run by %s, its founder and writer. Pages show the author and the date they were last updated.', 'computingskill' ), '<a href="' . esc_url( $author['url'] ) . '">' . esc_html( $author['name'] ) . '</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput
?></p>
<h2><?php esc_html_e( 'Where the numbers come from', 'computingskill' ); ?></h2>
<p><?php
	/* translators: %s: link to the Methodology page */
	printf( esc_html__( 'Hardware specifications come from manufacturers’ published information, and game requirements from the publishers’ official listings. Where a publisher gives only a minimum spec, the page labels any higher spec as our own suggestion. Each calculator’s formula and data sources are set out on the %s page.', 'computingskill' ), cs_policy_link( 'methodology', __( 'Methodology', 'computingskill' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
?></p>
<h2><?php esc_html_e( 'Estimates, clearly labelled', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'We don’t run a hardware lab or benchmark physical parts. Results are estimates built from manufacturer specifications and stated formulas, and we label them that way wherever a number appears. The CPU and GPU performance scores behind the bottleneck, FPS and performance calculators are our own gaming index, placed from publicly reported third-party review averages. We don’t invent statistics, benchmark figures, user counts or reviews.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'Independence', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'The site carries no ads, affiliate links or sponsored placements, and it is not affiliated with any hardware maker. No company pays to appear in, or to be ranked by, a calculator or comparison.', 'computingskill' ); ?></p>
<h2><?php esc_html_e( 'Original writing', 'computingskill' ); ?></h2>
<p><?php esc_html_e( 'Pages are written for this site, not copied or lightly reworded from other sources.', 'computingskill' ); ?></p>
<h2 id="corrections"><?php esc_html_e( 'Corrections', 'computingskill' ); ?></h2>
<p><?php
	/* translators: %s: link to the Contact page */
	printf( esc_html__( 'If a spec, formula or explanation looks wrong, tell us through the %s. Mention the page, the inputs you used and what you expected. We check the report against the manufacturer’s or publisher’s own source, and once a mistake is confirmed we fix the page, which also moves its “Last updated” date.', 'computingskill' ), cs_policy_link( 'contact', __( 'Contact page', 'computingskill' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
?></p>
<h2><?php esc_html_e( 'Related pages', 'computingskill' ); ?></h2>
<p><?php echo cs_policy_link( 'about-us', __( 'About Us', 'computingskill' ) ) . ' · ' . cs_policy_link( 'methodology', __( 'Methodology', 'computingskill' ) ) . ' · ' . cs_policy_link( 'disclaimer', __( 'Disclaimer', 'computingskill' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* One-time date refresh and cache purge                               */
/* ------------------------------------------------------------------ */

function cs_legal_rev_apply() {
	if ( wp_installing() || get_option( 'cs_legal_rev' ) === CS_LEGAL_REV ) {
		return;
	}
	if ( ! add_option( 'cs_legal_rev_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_legal_rev_lock' ) > 300 ) {
			delete_option( 'cs_legal_rev_lock' );
		}
		return;
	}
	global $wpdb;
	$touched = array();
	// E3: Terms of Use added (its text is built by cs_terms_of_use_html()).
	foreach ( array( 'privacy-policy', 'disclaimer', 'terms-of-use' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$touched[] = $page->ID;
			$wpdb->update(
				$wpdb->posts,
				array( 'post_modified' => current_time( 'mysql' ), 'post_modified_gmt' => current_time( 'mysql', true ) ),
				array( 'ID' => $page->ID ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			clean_post_cache( $page->ID );
		}
	}
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	// D1 cleared the whole cache once (footer, headers, contact token all
	// changed). E1 (SEO-01): a later CS_LEGAL_REV only changes these
	// pages, so only they are purged (no-op without LiteSpeed).
	cs_perf_purge_posts( $touched );
	update_option( 'cs_legal_rev', CS_LEGAL_REV, true );
	delete_option( 'cs_legal_rev_lock' );
}
add_action( 'init', 'cs_legal_rev_apply', 40 );

/* ------------------------------------------------------------------ */
/* Security headers (6.5)                                              */
/* ------------------------------------------------------------------ */

/**
 * Safe defaults only: no script-src rules, so nothing on the site can be
 * blocked. HSTS has no includeSubDomains/preload, so a subdomain without
 * HTTPS keeps working. Sent with replace=false so a CSP the host already
 * adds (upgrade-insecure-requests) stays.
 */
function cs_security_headers() {
	if ( headers_sent() || is_admin() ) {
		return;
	}
	// Once the .htaccess block from inc/hardening.php is live, the server
	// sends these four on every response (E2 SEC-02); sending them here too
	// would only duplicate them.
	if ( ! function_exists( 'cs_harden_server_headers' ) || ! cs_harden_server_headers() ) {
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000' );
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()' );
	}
	header( 'X-Frame-Options: SAMEORIGIN' );
	// No script-src: the theme has inline scripts (D1). object-src and
	// base-uri cost nothing and block plugin embeds and <base> hijacks (SEC-03).
	header( "Content-Security-Policy: frame-ancestors 'self'; object-src 'none'; base-uri 'self'", false );
}
add_action( 'send_headers', 'cs_security_headers' );

/* ------------------------------------------------------------------ */
/* Public user list (6.6)                                              */
/* ------------------------------------------------------------------ */

/**
 * /wp-json/wp/v2/users showed the admin login slug to anyone. The editor
 * still needs it, so only signed-in editors get an answer.
 */
function cs_rest_users_private( $result, $server, $request ) {
	if ( null !== $result || 0 !== strpos( $request->get_route(), '/wp/v2/users' ) || current_user_can( 'edit_posts' ) ) {
		return $result;
	}
	return new WP_Error( 'rest_user_cannot_view', __( 'Sorry, you are not allowed to list users.', 'computingskill' ), array( 'status' => rest_authorization_required_code() ) );
}
add_filter( 'rest_pre_dispatch', 'cs_rest_users_private', 10, 3 );

// oEmbed data linked to /author/{login-slug}/; point it at the public author.
function cs_oembed_author( $data ) {
	$author = cs_site_author();
	$data['author_name'] = $author['name'];
	$data['author_url']  = $author['url'];
	return $data;
}
add_filter( 'oembed_response_data', 'cs_oembed_author', 20 );
