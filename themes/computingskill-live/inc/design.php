<?php
/**
 * Phase 8 (Oct 2026) design helpers: line icons, the header wordmark,
 * per-resolution "load bars" for bottleneck results, and the self-hosted
 * font preload.
 *
 * Presentation only. Every number shown by cs_bn_load_bars() comes straight
 * from cs_calculate_bottleneck() (inc/bottleneck.php); nothing here does
 * any maths of its own beyond scaling a bar's width.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inline SVG line icon by key, or '' for an unknown key.
 * Icons use currentColor so CSS sets their colour.
 */
function cs_icon_svg( $key ) {
	$paths = array(
		'cpu'    => '<rect x="6" y="6" width="12" height="12" rx="2"/><rect x="9.5" y="9.5" width="5" height="5" rx="1"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
		'gpu'    => '<rect x="2" y="6" width="17" height="11" rx="2"/><circle cx="10.5" cy="11.5" r="3"/><path d="M19 9h3M19 14h3M5 17v3M9 17v3"/>',
		'psu'    => '<path d="M13 2 5 13h6l-1 9 8-11h-6z"/>',
		'fps'    => '<path d="M3 17l5-6 4 3 6-8 3 3"/><path d="M3 21h18"/>',
		'cost'   => '<path d="M20 12 12 20 4 12V4h8z"/><circle cx="8.5" cy="8.5" r="1.5"/>',
		'check'  => '<path d="M5 12l4 4 10-10"/>',
		'doc'    => '<path d="M6 2h9l4 4v16H6z"/><path d="M9 11h7M9 15h7M9 7h3"/>',
		'link'   => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'phone'  => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
	);
	if ( ! isset( $paths[ $key ] ) ) {
		return '';
	}
	return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $key ] . '</svg>';
}

/**
 * Text wordmark for the header and footer. The uploaded logo PNG has dark
 * "Computing" lettering on a transparent background, which disappears on
 * the dark header, so the theme draws the same mark (blue chip tile +
 * "Computing" + blue "Skill") in markup that follows the colour scheme.
 * The Customizer logo stays uploaded and is still what Rank Math's
 * Organization schema uses.
 */
function cs_wordmark() {
	$name = get_bloginfo( 'name' );
	$tile = '<svg class="cs-wordmark-tile" viewBox="0 0 32 32" width="32" height="32" aria-hidden="true" focusable="false"><rect width="32" height="32" rx="8" fill="currentColor"/><g fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round"><rect x="10" y="10" width="12" height="12" rx="2"/><path d="M13 6.5v3.5M16 6.5v3.5M19 6.5v3.5M13 22v3.5M16 22v3.5M19 22v3.5M6.5 13h3.5M6.5 16h3.5M6.5 19h3.5M22 13h3.5M22 16h3.5M22 19h3.5"/></g></svg>';
	// Split "Computing Skill" so the second word takes the accent colour, as
	// in the logo. Any other site name is printed as one word.
	if ( preg_match( '/^(Computing)\s*(Skill)$/i', $name, $m ) ) {
		$text = '<span class="cs-wordmark-a">' . esc_html( $m[1] ) . '</span><span class="cs-wordmark-b">' . esc_html( $m[2] ) . '</span>';
	} else {
		$text = '<span class="cs-wordmark-a">' . esc_html( $name ) . '</span>';
	}
	return $tile . '<span class="cs-wordmark-text">' . $text . '</span>';
}

/**
 * Per-resolution bars for a cs_calculate_bottleneck() result.
 * Bar length = percent on the engine's 0-60 scale (60 is its cap).
 *
 * @param array $results Engine output keyed 1080p / 1440p / 4k.
 * @param bool  $compact Short labels ("20% CPU") for small cards.
 */
function cs_bn_load_bars( $results, $compact = false ) {
	$res_labels = array( '1080p' => '1080p', '1440p' => '1440p', '4k' => '4K' );
	$html       = '<div class="cs-lb" role="list">';
	foreach ( $results as $res => $r ) {
		$component = $r['component'];
		$percent   = (int) $r['percent'];
		if ( 'balanced' === $component ) {
			$word = __( 'balanced', 'computingskill' );
		} elseif ( 'cpu' === $component ) {
			$word = $compact ? __( 'CPU', 'computingskill' ) : __( 'CPU-bound', 'computingskill' );
		} else {
			$word = $compact ? __( 'GPU', 'computingskill' ) : __( 'GPU-bound', 'computingskill' );
		}
		$width = max( 4, min( 100, round( $percent / 60 * 100 ) ) );
		$label = isset( $res_labels[ $res ] ) ? $res_labels[ $res ] : $res;
		$html .= '<div class="cs-lb-row cs-lb-' . esc_attr( $component ) . '" role="listitem">';
		$html .= '<span class="cs-lb-res">' . esc_html( $label ) . '</span>';
		$html .= '<span class="cs-lb-track" aria-hidden="true"><span class="cs-lb-fill" style="width:' . esc_attr( $width ) . '%"></span></span>';
		$html .= '<span class="cs-lb-val">' . esc_html( $percent . '% ' . $word ) . '</span>';
		$html .= '</div>';
	}
	return $html . '</div>';
}

/**
 * Preloads the heading font so the H1 doesn't swap late.
 */
function cs_preload_fonts() {
	echo '<link rel="preload" href="' . esc_url( get_template_directory_uri() . '/assets/fonts/chakra-petch-700.woff2' ) . '" as="font" type="font/woff2" crossorigin />' . "\n";
}
add_action( 'wp_head', 'cs_preload_fonts', 2 );
