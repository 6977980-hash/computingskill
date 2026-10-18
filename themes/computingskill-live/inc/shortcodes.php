<?php
/**
 * [cs_calculator type="psu"] shortcode — the calculator mount point.
 *
 * Content editors (and the bundled WXR import) place this shortcode near
 * the top of a calculator/tool page's content, right after the intro
 * paragraph. It renders an empty, accessible container that
 * assets/js/calculators.js finds and initializes client-side. This keeps
 * ALL calculator logic in one enqueued JS bundle instead of inline
 * <script> in the post editor.
 *
 * Usage: [cs_calculator type="psu" title="PSU Calculator"]
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize a broken delimiter around ANY [cs_xxx attr="..."] shortcode
 * attribute value, before WordPress parses shortcodes. WP's shortcode
 * regex only recognizes a straight " or ' as a quoted-attribute delimiter.
 *
 * Root cause confirmed live during the Sep 2026 audit by reading raw post
 * content directly (not just rendered HTML): attributes across the site
 * were authored/imported as e.g.
 *   [cs_faq_item q=&quot;Are these calculators as accurate...&quot;]
 *   [cs_related title=&quot;Related Pages&quot; links=&quot;...&quot;]
 * — i.e. the six-character text "&quot;" instead of an actual " character.
 * Since the attribute is then followed by `&`, not a real quote, WordPress
 * treats the value as *unquoted* and truncates it at the first space —
 * this is why FAQ headings rendered as just "Are, "Which, "Do (corrupting
 * FAQPage JSON-LD too), and separately why a "Related Pages" heading
 * rendered as the literal fragment '"Related' on the About Us page and
 * likely others. This also defensively handles curly/smart quotes (”/“)
 * used the same way, in case a future edit or paste hits the same failure
 * mode a different way (e.g. pasted from a word processor).
 *
 * Originally written narrowly for [cs_faq_item] only; broadened during the
 * Sep 2026 audit to cover every cs_* shortcode tag (cs_related, cs_faq_item,
 * cs_calculator, cs_hardware_table, etc.) after the same corruption pattern
 * was found on cs_related's title= attribute too. Already-correct tags
 * using real straight quotes are left untouched — the inner regex only
 * matches the broken &quot;/smart-quote delimiter forms.
 *
 * Fixed at the template layer, not by editing every live page's content,
 * so it recovers the full original attribute text automatically wherever
 * this happens — on pages already affected, and on any new content that
 * hits the same failure mode later, anywhere on the site. Runs on
 * `the_content` at priority 8, before WordPress's own shortcode
 * processing (priority 11).
 */
function cs_normalize_shortcode_quotes( $content ) {
	if ( false === strpos( $content, '[cs_' ) ) {
		return $content;
	}
	return preg_replace_callback(
		'/\[cs_[a-zA-Z_]+[^\]]*\]/su',
		function ( $tag_match ) {
			return preg_replace_callback(
				'/([a-zA-Z_]+)=(&quot;|[\x{201C}\x{201D}\x{2033}])(.*?)(&quot;|[\x{201C}\x{201D}\x{2033}])/su',
				function ( $m ) {
					$value = html_entity_decode( $m[3], ENT_QUOTES );
					$value = str_replace( '"', '&quot;', $value );
					return $m[1] . '="' . $value . '"';
				},
				$tag_match[0]
			);
		},
		$content
	);
}
add_filter( 'the_content', 'cs_normalize_shortcode_quotes', 8 );

function cs_calculator_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'type'  => '',
		'title' => __( 'Calculator', 'computingskill' ),
	), $atts, 'cs_calculator' );

	$type = sanitize_key( $atts['type'] );
	if ( empty( $type ) ) {
		return '';
	}

	cs_enqueue_calculators();

	ob_start();
	?>
	<div class="cs-calculator" id="cs-calculator-<?php echo esc_attr( $type ); ?>" data-calc="<?php echo esc_attr( $type ); ?>" role="group" aria-label="<?php echo esc_attr( $atts['title'] ); ?>">
		<noscript>
			<p class="cs-noscript-notice"><?php esc_html_e( 'This calculator requires JavaScript to be enabled in your browser.', 'computingskill' ); ?></p>
		</noscript>
		<div class="cs-calc-fields" data-role="fields"></div>
		<div class="cs-calc-actions">
			<button type="button" class="cs-calc-submit" data-role="submit"><?php esc_html_e( 'Calculate', 'computingskill' ); ?></button>
			<button type="button" class="cs-calc-reset" data-role="reset"><?php esc_html_e( 'Reset', 'computingskill' ); ?></button>
		</div>
		<div class="cs-calc-result" data-role="result" aria-live="polite"></div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cs_calculator', 'cs_calculator_shortcode' );

/**
 * [cs_related links="slug1,slug2,slug3"] — renders a "Related calculators
 * & guides" card grid from a comma-separated list of page/post slugs.
 * Used to build the deliberate internal-linking network from section 24
 * without hand-writing <a> tags (and the risk of broken links) in content.
 */
function cs_related_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'links' => '',
		'title' => __( 'Related Tools & Guides', 'computingskill' ),
	), $atts, 'cs_related' );

	$slugs = array_filter( array_map( 'trim', explode( ',', $atts['links'] ) ) );
	if ( empty( $slugs ) ) {
		return '';
	}

	// Resolve redirected slugs (inc/redirects.php) to their destination so
	// related-links grids never link through a 301, and drop the duplicate
	// that creates (e.g. a list naming both screen-size-calculator and
	// monitor-size-calculator now shows Monitor Size once).
	if ( function_exists( 'cs_canonical_slug' ) ) {
		$slugs = array_values( array_unique( array_map( 'cs_canonical_slug', $slugs ) ) );
		$current = get_post_field( 'post_name' );
		$slugs   = array_values( array_diff( $slugs, array( $current ) ) );
	}

	$items = array();
	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			$page = get_page_by_path( $slug, OBJECT, array( 'cs_calculator', 'cs_tool', 'cs_hardware', 'cs_guide', 'cs_comparison', 'cs_resource', 'cs_game', 'post' ) );
		}
		if ( $page && 'publish' === $page->post_status ) {
			$items[] = $page;
		}
	}

	if ( empty( $items ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="cs-related">
		<h2><?php echo esc_html( $atts['title'] ); ?></h2>
		<ul class="cs-related-grid">
			<?php foreach ( $items as $item ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $item ) ); ?>"><?php echo esc_html( get_the_title( $item ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cs_related', 'cs_related_shortcode' );

/**
 * [cs_faq] ... [cs_faq_item q="Question?"]Answer text[/cs_faq_item] ... [/cs_faq]
 * Renders an accessible FAQ block AND (when safe per cs_schema_should_emit)
 * queues its Q/A pairs for FAQPage JSON-LD output in wp_footer.
 */
function cs_faq_shortcode( $atts, $content = null ) {
	$GLOBALS['cs_current_faq_items'] = array();
	$rendered_items                  = do_shortcode( $content );
	ob_start();
	?>
	<div class="cs-faq">
		<h2><?php esc_html_e( 'Frequently Asked Questions', 'computingskill' ); ?></h2>
		<?php echo $rendered_items; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped sub-shortcode output only. ?>
	</div>
	<?php
	$html = ob_get_clean();

	if ( ! empty( $GLOBALS['cs_current_faq_items'] ) ) {
		global $cs_faq_schema_queue;
		if ( ! is_array( $cs_faq_schema_queue ) ) {
			$cs_faq_schema_queue = array();
		}
		$cs_faq_schema_queue = array_merge( $cs_faq_schema_queue, $GLOBALS['cs_current_faq_items'] );
	}

	return $html;
}
add_shortcode( 'cs_faq', 'cs_faq_shortcode' );

/**
 * [cs_quick_answer]One to three sentence direct answer.[/cs_quick_answer]
 *
 * Renders a short, visually distinct "quick answer" box near the top of a
 * calculator/tool/guide page, for readers (and AI answer engines) who want
 * the direct takeaway before the full explanation below it. No attributes
 * are used deliberately, so this shortcode never hits the &quot;-delimiter
 * shortcode-attribute bug that other cs_* shortcodes had to be patched for.
 */
function cs_quick_answer_shortcode( $atts, $content = '' ) {
	$content = trim( (string) $content );
	if ( '' === $content ) {
		return '';
	}

	$answer_html = wpautop( $content );

	ob_start();
	?>
	<div class="cs-quick-answer">
		<p class="cs-quick-answer-label"><?php esc_html_e( 'Quick answer', 'computingskill' ); ?></p>
		<div class="cs-quick-answer-body"><?php echo wp_kses_post( $answer_html ); ?></div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cs_quick_answer', 'cs_quick_answer_shortcode' );

function cs_faq_item_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts( array( 'q' => '' ), $atts, 'cs_faq_item' );
	if ( empty( $atts['q'] ) ) {
		return '';
	}

	$answer_html = wpautop( trim( $content ) );

	if ( isset( $GLOBALS['cs_current_faq_items'] ) ) {
		$GLOBALS['cs_current_faq_items'][] = array(
			'question' => wp_strip_all_tags( $atts['q'] ),
			'answer'   => wp_strip_all_tags( $answer_html ),
		);
	}

	ob_start();
	?>
	<div class="cs-faq-item">
		<h3 class="cs-faq-question"><?php echo esc_html( $atts['q'] ); ?></h3>
		<div class="cs-faq-answer"><?php echo wp_kses_post( $answer_html ); ?></div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cs_faq_item', 'cs_faq_item_shortcode' );

/**
 * [cs_hardware_table type="gpu" items="rtx-4060,rtx-4070"] renders a
 * responsive spec comparison table from the verified hardware dataset.
 */
function cs_hardware_table_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'type'  => 'gpu',
		'items' => '',
	), $atts, 'cs_hardware_table' );

	$keys = array_filter( array_map( 'trim', explode( ',', $atts['items'] ) ) );
	if ( empty( $keys ) ) {
		return '';
	}

	$data = 'cpu' === $atts['type'] ? cs_get_cpu_data() : cs_get_gpu_data();

	ob_start();
	echo '<div class="cs-table-wrap"><table class="cs-hardware-table">';
	if ( 'cpu' === $atts['type'] ) {
		echo '<thead><tr><th>' . esc_html__( 'Model', 'computingskill' ) . '</th><th>' . esc_html__( 'Cores/Threads', 'computingskill' ) . '</th><th>' . esc_html__( 'Base / Boost Clock', 'computingskill' ) . '</th><th>' . esc_html__( 'TDP', 'computingskill' ) . '</th><th>' . esc_html__( 'Socket', 'computingskill' ) . '</th></tr></thead><tbody>';
		foreach ( $keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}
			$c = $data[ $key ];
			echo '<tr><td>' . esc_html( $c['name'] ) . '</td><td>' . esc_html( $c['cores'] . 'C / ' . $c['threads'] . 'T' ) . '</td><td>' . esc_html( $c['base_clock_ghz'] . ' GHz / ' . $c['boost_clock_ghz'] . ' GHz' ) . '</td><td>' . esc_html( $c['tdp_w'] . 'W' ) . '</td><td>' . esc_html( $c['socket'] ) . '</td></tr>';
		}
	} else {
		echo '<thead><tr><th>' . esc_html__( 'Model', 'computingskill' ) . '</th><th>' . esc_html__( 'VRAM', 'computingskill' ) . '</th><th>' . esc_html__( 'Memory Bus', 'computingskill' ) . '</th><th>' . esc_html__( 'TDP', 'computingskill' ) . '</th><th>' . esc_html__( 'Recommended PSU', 'computingskill' ) . '</th></tr></thead><tbody>';
		foreach ( $keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}
			$g = $data[ $key ];
			echo '<tr><td>' . esc_html( $g['name'] ) . '</td><td>' . esc_html( $g['vram_gb'] . ' GB ' . $g['memory_type'] ) . '</td><td>' . esc_html( $g['memory_bus'] . '-bit' ) . '</td><td>' . esc_html( $g['tdp_w'] . 'W' ) . '</td><td>' . esc_html( $g['recommended_psu_w'] . 'W+' ) . '</td></tr>';
		}
	}
	echo '</tbody></table></div>';

	// Added during the Sep 2026 trust/AI-search audit: link each shown part
	// back to its manufacturer's own product/specifications page, so this
	// table cites its sources instead of relying on the dataset's own
	// unlinked header comment.
	$cited_parts = array();
	foreach ( $keys as $key ) {
		if ( isset( $data[ $key ] ) ) {
			$cited_parts[] = array(
				'name'       => $data[ $key ]['name'],
				'source_url' => isset( $data[ $key ]['source_url'] ) ? $data[ $key ]['source_url'] : null,
			);
		}
	}
	cs_hardware_source_line( $cited_parts );

	return ob_get_clean();
}
add_shortcode( 'cs_hardware_table', 'cs_hardware_table_shortcode' );

/**
 * Reciprocal internal linking for the Games section (section 24 network).
 *
 * Individual /games/{slug}/ pages already link OUT to the calculators
 * below (see single-cs_game.php). This prints the matching link BACK to
 * a curated set of popular games on the handful of calculator/guide
 * pages where a "can I run this game" reader is clearly on-topic —
 * without needing to hand-edit each page's stored content (which isn't
 * something we can safely automate against a live site). Call this from
 * a page template right after the_content().
 */
function cs_maybe_print_games_crosslink() {
	if ( ! function_exists( 'cs_get_games_data' ) ) {
		return;
	}
	$eligible_slugs = array(
		'pc-bottleneck-calculator',
		// fps-calculator: lists every game page instead (D7, inc/internal-links.php).
		'cpu-vs-gpu-calculator',
		'gpu-vram-calculator',
		'gpu-performance-calculator',
		'pc-upgrade-calculator',
		'how-much-vram-do-i-need',
		'how-to-choose-a-gaming-gpu',
		'how-to-improve-fps',
		'how-to-upgrade-a-gaming-pc',
		'cpu-bottleneck-explained',
		'gpu-bottleneck-explained',
		'vram',
	);
	$slug = get_post_field( 'post_name' );
	if ( ! in_array( $slug, $eligible_slugs, true ) ) {
		return;
	}
	echo cs_related_shortcode( array(
		'title' => __( 'Checking a Specific Game?', 'computingskill' ),
		'links' => 'cyberpunk-2077,gta-5-enhanced,elden-ring,baldurs-gate-3,starfield',
	) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped sub-output only, see cs_related_shortcode().
}
