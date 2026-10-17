<?php
/**
 * Google Analytics 4 (gtag.js) — added during the Sep 2026 audit.
 *
 * GA4 can't be switched on from the theme alone: it needs the site
 * owner's own Measurement ID (G-XXXXXXXXXX) from their Google Analytics
 * property. This adds a single field for it under Settings → General, and
 * prints the standard gtag.js snippet only once an ID has been saved — so
 * nothing loads, and no third-party request is made, until the owner
 * deliberately turns it on.
 *
 * Not printed for logged-in editors/admins (so the owner's own browsing
 * doesn't inflate reports), and skipped entirely if Google Site Kit is
 * active, since Site Kit places its own tag and two tags would double-count
 * every visit. If Rank Math's Analytics module is set to "Install analytics
 * code", leave this field empty — use one method only.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accepts only a well-formed GA4 Measurement ID; anything else saves as
 * empty (which keeps the tag off) rather than printing arbitrary text into
 * a script tag.
 */
function cs_sanitize_ga4_id( $value ) {
	$value = strtoupper( trim( (string) $value ) );
	return preg_match( '/^G-[A-Z0-9]{4,20}$/', $value ) ? $value : '';
}

function cs_register_ga4_setting() {
	register_setting(
		'general',
		'cs_ga4_measurement_id',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'cs_sanitize_ga4_id',
			'default'           => '',
			'show_in_rest'      => true,
		)
	);
}
add_action( 'admin_init', 'cs_register_ga4_setting' );
add_action( 'rest_api_init', 'cs_register_ga4_setting' );

// add_settings_field() only exists in wp-admin, so the visible field is
// registered separately from the setting itself (which REST also needs).
function cs_add_ga4_settings_field() {
	add_settings_field(
		'cs_ga4_measurement_id',
		__( 'GA4 Measurement ID', 'computingskill' ),
		'cs_render_ga4_field',
		'general',
		'default',
		array( 'label_for' => 'cs_ga4_measurement_id' )
	);
}
add_action( 'admin_init', 'cs_add_ga4_settings_field' );

function cs_render_ga4_field() {
	$value = get_option( 'cs_ga4_measurement_id', '' );
	printf(
		'<input type="text" id="cs_ga4_measurement_id" name="cs_ga4_measurement_id" value="%s" class="regular-text" placeholder="G-XXXXXXXXXX" /><p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'From Google Analytics → Admin → Data streams → your web stream. Leave empty to keep analytics off.', 'computingskill' )
	);
}

/**
 * Whether the GA4 tag (and so the consent banner) is printed on this request.
 */
function cs_ga4_active_id() {
	$id = cs_sanitize_ga4_id( get_option( 'cs_ga4_measurement_id', '' ) );
	if ( '' === $id || defined( 'GOOGLESITEKIT_VERSION' ) ) {
		return '';
	}
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return '';
	}
	return $id;
}

/**
 * Consent first (deep audit 6.1, Oct 2026). GA4 used to load for every
 * visitor. Now Google Consent Mode starts with everything denied, and
 * gtag.js is not even downloaded until the visitor presses "Accept
 * analytics" in the banner (cs_print_consent_banner()). The choice lives in
 * a first-party cookie, cs_consent=granted|denied, for 180 days. A browser
 * that sends Global Privacy Control counts as "denied" until the visitor
 * says otherwise. Google Signals and ad personalisation stay off, which the
 * Privacy Policy text in inc/legal.php relies on.
 */
function cs_print_ga4_tag() {
	$id = cs_ga4_active_id();
	if ( '' === $id ) {
		return;
	}
	?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', { analytics_storage: 'denied', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
window.csGa = (function () {
	var id = '<?php echo esc_js( $id ); ?>', loaded = false;
	function load() {
		if (loaded) { return; }
		loaded = true;
		window['ga-disable-' + id] = false;
		gtag('consent', 'update', { analytics_storage: 'granted' });
		gtag('js', new Date());
		gtag('config', id, { allow_google_signals: false, allow_ad_personalization_signals: false });
		// gtag.js (~150 KB) loads after the page has finished loading so it
		// doesn't compete with the page itself on mobile (PageSpeed, Oct 2026).
		function inject() {
			var s = document.createElement('script');
			s.async = true;
			s.src = 'https://www.googletagmanager.com/gtag/js?id=' + id;
			document.head.appendChild(s);
		}
		if (document.readyState === 'complete') { inject(); } else { window.addEventListener('load', inject); }
	}
	function choice() {
		var m = document.cookie.match(/(?:^|; )cs_consent=(granted|denied)/);
		if (m) { return m[1]; }
		return navigator.globalPrivacyControl ? 'denied' : '';
	}
	function set(value) {
		document.cookie = 'cs_consent=' + value + '; max-age=15552000; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
		if (value === 'granted') { load(); return; }
		window['ga-disable-' + id] = true;
		gtag('consent', 'update', { analytics_storage: 'denied' });
		// Remove cookies GA set earlier on this site (_ga, _ga_XXXX).
		document.cookie.split('; ').forEach(function (c) {
			var name = c.split('=')[0];
			if (/^_ga(_|$)/.test(name)) {
				var host = location.hostname.replace(/^www\./, '');
				['', '; domain=' + host, '; domain=.' + host].forEach(function (d) {
					document.cookie = name + '=; max-age=0; path=/' + d;
				});
			}
		});
	}
	if (choice() === 'granted') { load(); }
	return { id: id, choice: choice, set: set };
})();
</script>
	<?php
}
add_action( 'wp_head', 'cs_print_ga4_tag', 1 );

/**
 * The banner itself. Built in JS so the cached HTML is the same for every
 * visitor; it only appears when no choice has been stored yet, or when the
 * visitor opens it again from "Cookie settings" in the footer. Both buttons
 * look the same on purpose: rejecting must be as easy as accepting.
 */
function cs_print_consent_banner() {
	if ( '' === cs_ga4_active_id() ) {
		return;
	}
	$text    = __( 'We use Google Analytics cookies to see which pages and calculators people use, but only if you allow it. No ads, no selling of data.', 'computingskill' );
	$policy  = __( 'Privacy Policy', 'computingskill' );
	$accept  = __( 'Accept analytics', 'computingskill' );
	$reject  = __( 'Reject', 'computingskill' );
	$label   = __( 'Cookie consent', 'computingskill' );
	?>
<script>
(function () {
	if (!window.csGa) { return; }
	var box;
	function close() { if (box) { box.remove(); box = null; } }
	function open() {
		if (box) { return; }
		box = document.createElement('div');
		box.className = 'cs-consent';
		box.setAttribute('role', 'region');
		box.setAttribute('aria-label', <?php echo wp_json_encode( $label ); ?>);
		var p = document.createElement('p');
		p.textContent = <?php echo wp_json_encode( $text ); ?> + ' ';
		var a = document.createElement('a');
		a.href = <?php echo wp_json_encode( home_url( '/privacy-policy/#analytics' ) ); ?>;
		a.textContent = <?php echo wp_json_encode( $policy ); ?>;
		p.appendChild(a);
		var row = document.createElement('div');
		row.className = 'cs-consent-actions';
		[['granted', <?php echo wp_json_encode( $accept ); ?>], ['denied', <?php echo wp_json_encode( $reject ); ?>]].forEach(function (b) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.textContent = b[1];
			btn.addEventListener('click', function () { window.csGa.set(b[0]); close(); });
			row.appendChild(btn);
		});
		box.appendChild(p);
		box.appendChild(row);
		document.body.appendChild(box);
	}
	document.querySelectorAll('[data-cs-consent-open]').forEach(function (el) {
		el.hidden = false;
		el.addEventListener('click', function (e) { e.preventDefault(); open(); });
	});
	if (!window.csGa.choice()) { open(); }
})();
</script>
	<?php
}
add_action( 'wp_footer', 'cs_print_consent_banner', 20 );
