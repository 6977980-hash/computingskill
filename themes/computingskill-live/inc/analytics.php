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

function cs_print_ga4_tag() {
	$id = cs_sanitize_ga4_id( get_option( 'cs_ga4_measurement_id', '' ) );
	if ( '' === $id ) {
		return;
	}
	if ( defined( 'GOOGLESITEKIT_VERSION' ) ) {
		return;
	}
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return;
	}
	?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo esc_js( $id ); ?>');
// gtag.js (~150 KB) loads after the page has finished loading so it doesn't
// compete with the page itself on mobile (PageSpeed, Oct 2026). The calls
// above are queued in dataLayer and sent once it arrives.
(function () {
	function load() {
		var s = document.createElement('script');
		s.async = true;
		s.src = 'https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( $id ); ?>';
		document.head.appendChild(s);
	}
	if (document.readyState === 'complete') { load(); } else { window.addEventListener('load', load); }
})();
</script>
	<?php
}
add_action( 'wp_head', 'cs_print_ga4_tag', 1 );
