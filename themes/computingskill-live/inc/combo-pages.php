<?php
/**
 * Creates the /bottleneck/{cpu}-{gpu}/ posts listed in
 * cs_get_bottleneck_pairs() that don't exist yet (Phase 7 audit fix,
 * Oct 2026).
 *
 * The first 30 combo posts came from a WXR import. New pairs are added to
 * data/hardware-data.php only; this file turns them into cs_bottleneck
 * posts the same shape as the imported ones (title, slug, excerpt, empty
 * content, cs_cpu / cs_gpu meta). All page text is rendered by
 * single-cs_bottleneck.php from the engine, so nothing is stored per post.
 *
 * Idempotent: it runs once per change of the pair list (hash stored in an
 * option), never creates a slug that already exists in any status, and
 * never touches existing posts. A combo post an editor drafted or trashed
 * stays that way.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cs_combo_pages_sync() {
	if ( wp_installing() || ! post_type_exists( 'cs_bottleneck' ) ) {
		return;
	}

	$pairs = cs_get_bottleneck_pairs();
	$hash  = md5( wp_json_encode( $pairs ) );
	if ( get_option( 'cs_combo_pages_synced' ) === $hash ) {
		return;
	}

	// add_option() fails if the row exists, so only one request runs this.
	if ( ! add_option( 'cs_combo_pages_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_combo_pages_lock' ) > 300 ) {
			delete_option( 'cs_combo_pages_lock' );
		}
		return;
	}

	$cpus     = cs_get_cpu_data();
	$gpus     = cs_get_gpu_data();
	$existing = get_posts( array(
		'post_type'      => 'cs_bottleneck',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	) );
	$slugs  = array();
	$author = 0;
	foreach ( $existing as $post ) {
		// Trashed posts keep their slug with a __trashed suffix.
		$slugs[ preg_replace( '/__trashed$/', '', $post->post_name ) ] = true;
		if ( ! $author && 'publish' === $post->post_status ) {
			$author = (int) $post->post_author;
		}
	}

	$ok = true;
	foreach ( $pairs as $pair ) {
		$slug = $pair['cpu'] . '-' . $pair['gpu'];
		if ( isset( $slugs[ $slug ] ) || ! isset( $cpus[ $pair['cpu'] ], $gpus[ $pair['gpu'] ] ) ) {
			continue;
		}
		$cpu = $cpus[ $pair['cpu'] ]['name'];
		$gpu = $gpus[ $pair['gpu'] ]['name'];
		$id  = wp_insert_post( array(
			'post_type'      => 'cs_bottleneck',
			'post_status'    => 'publish',
			'post_name'      => $slug,
			'post_author'    => $author,
			/* translators: 1: CPU name, 2: GPU name */
			'post_title'     => sprintf( __( '%1$s + %2$s Bottleneck Calculator', 'computingskill' ), $cpu, $gpu ),
			/* translators: 1: CPU name, 2: GPU name */
			'post_excerpt'   => sprintf( __( 'Bottleneck estimate for the %1$s paired with the %2$s at 1080p, 1440p and 4K, with upgrade guidance.', 'computingskill' ), $cpu, $gpu ),
			'post_content'   => '',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'meta_input'     => array(
				'cs_cpu' => $pair['cpu'],
				'cs_gpu' => $pair['gpu'],
			),
		), true );
		if ( is_wp_error( $id ) ) {
			$ok = false;
		} else {
			$slugs[ $slug ] = true;
		}
	}

	if ( $ok ) {
		update_option( 'cs_combo_pages_synced', $hash, false );
	}
	delete_option( 'cs_combo_pages_lock' );
}
add_action( 'init', 'cs_combo_pages_sync', 30 );
