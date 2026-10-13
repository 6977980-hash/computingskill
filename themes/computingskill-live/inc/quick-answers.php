<?php
/**
 * Quick answer first (Oct 2026 audit, FIX-13).
 *
 * The CPU and GPU bottleneck guides already carry a [cs_quick_answer] box
 * in their post content, but it sits below the lead image and the intro
 * paragraph, so the page opens with a symptom instead of the definition.
 * This moves the rendered box, unchanged, to the very top of the content.
 * The wording stays exactly as written in the post, which already agrees
 * with the per-resolution engine described on /methodology/ (CPU limits
 * show most at 1080p, GPU limits at 1440p/4K).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page slugs whose quick answer is moved to the top of the content.
 */
function cs_quick_answer_first_slugs() {
	return array( 'cpu-bottleneck-explained', 'gpu-bottleneck-explained' );
}

/**
 * Runs after do_shortcode (priority 11), so it moves the rendered box.
 * Pages without a box are left untouched.
 */
function cs_quick_answer_first( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! in_array( get_post_field( 'post_name' ), cs_quick_answer_first_slugs(), true ) ) {
		return $content;
	}

	$box = '#\s*<div class="cs-quick-answer">\s*<p class="cs-quick-answer-label">.*?</p>\s*<div class="cs-quick-answer-body">.*?</div>\s*</div>#s';
	if ( ! preg_match( $box, $content, $m, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$html    = trim( $m[0][0] );
	$content = substr_replace( $content, '', $m[0][1], strlen( $m[0][0] ) );

	return $html . "\n" . ltrim( $content );
}
add_filter( 'the_content', 'cs_quick_answer_first', 12 );
