<?php
/**
 * E5 (20 Oct 2026, full audit UX-01): mobile and accessibility helpers.
 *
 * Tables typed into post content are bare <table> elements. On a phone the
 * wider ones ran past the column and were cut off (body has overflow-x:
 * hidden), so they get the same .cs-table-wrap scroller the generated tables
 * use. main.js makes a wrap keyboard-focusable once it actually scrolls
 * (A11Y-04).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps every <table> that is not already inside a .cs-table-wrap.
 * Nested tables are left alone (the outer one is wrapped).
 *
 * @param string $content Post content.
 * @return string
 */
function cs_a11y_wrap_tables( $content ) {
	if ( is_feed() || false === stripos( $content, '<table' ) ) {
		return $content;
	}
	$out   = '';
	$pos   = 0;
	if ( ! preg_match_all( '#<table\b|</table>#i', $content, $m, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}
	$level = 0;
	$start = 0;
	foreach ( $m[0] as $tag ) {
		$is_open = '<' === $tag[0][0] && '/' !== $tag[0][1];
		if ( $is_open ) {
			if ( 0 === $level ) {
				$start = $tag[1];
			}
			$level++;
			continue;
		}
		if ( 0 === $level ) {
			continue; // Stray close tag.
		}
		$level--;
		if ( 0 !== $level ) {
			continue;
		}
		$end    = $tag[1] + strlen( $tag[0] );
		$before = substr( $content, $pos, $start - $pos );
		$table  = substr( $content, $start, $end - $start );
		$out   .= $before;
		// Already wrapped when the last tag before the table opens a .cs-table-wrap.
		$head = substr( $content, max( 0, $start - 300 ), min( 300, $start ) );
		if ( preg_match( '#<div\b[^>]*\bcs-table-wrap\b[^>]*>\s*$#i', $head ) ) {
			$out .= $table;
		} else {
			$out .= '<div class="cs-table-wrap">' . $table . '</div>';
		}
		$pos = $end;
	}
	return $out . substr( $content, $pos );
}
add_filter( 'the_content', 'cs_a11y_wrap_tables', 100 );
