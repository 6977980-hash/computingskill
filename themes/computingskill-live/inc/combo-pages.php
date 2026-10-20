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

	$ok      = true;
	$created = false;
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
			// "Bottleneck", not "Bottleneck Calculator": the page has no
			// calculator on it (deep audit D6, 2.10).
			/* translators: 1: CPU name, 2: GPU name */
			'post_title'     => sprintf( __( '%1$s + %2$s Bottleneck', 'computingskill' ), $cpu, $gpu ),
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
			$created        = true;
		}
	}
	// E3 (ONP-03): /bottleneck/ lists every combo page, so its date moves too.
	if ( $created && function_exists( 'cs_touch_hub_pages' ) ) {
		cs_touch_hub_pages( array( 'bottleneck' ) );
	}

	if ( $ok ) {
		update_option( 'cs_combo_pages_synced', $hash, true );
	}
	delete_option( 'cs_combo_pages_lock' );
}
add_action( 'init', 'cs_combo_pages_sync', 30 );

/**
 * "Last updated" for pages whose text the theme builds (Oct 2026).
 *
 * Combo and game pages store almost nothing in the post: their text comes
 * from data/*.php and inc/*-content.php. WordPress's modified date (shown in
 * the byline, schema and Rank Math's sitemap lastmod) therefore never moved
 * when that text was rewritten, and 30 combo pages still said September
 * after the October rewrites. When CS_GENERATED_CONTENT_REV changes, every
 * published cs_bottleneck and cs_game post gets its modified date set to
 * the moment the new code first runs, once.
 *
 * Bump the revision only in a push that really changes the text of those
 * pages, so the date stays honest.
 */
if ( ! defined( 'CS_GENERATED_CONTENT_REV' ) ) {
	define( 'CS_GENERATED_CONTENT_REV', '2026-10-18-d10' );
}

function cs_generated_pages_touch() {
	if ( wp_installing() || get_option( 'cs_generated_content_rev' ) === CS_GENERATED_CONTENT_REV ) {
		return;
	}
	if ( ! add_option( 'cs_generated_content_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_generated_content_lock' ) > 300 ) {
			delete_option( 'cs_generated_content_lock' );
		}
		return;
	}

	global $wpdb;
	$ids = get_posts( array(
		'post_type'      => array( 'cs_bottleneck', 'cs_game' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	$local = current_time( 'mysql' );
	$gmt   = current_time( 'mysql', true );
	foreach ( $ids as $id ) {
		// Direct update: wp_update_post() would add revisions and run every
		// save_post hook for a change that is only the date.
		$wpdb->update(
			$wpdb->posts,
			array( 'post_modified' => $local, 'post_modified_gmt' => $gmt ),
			array( 'ID' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( $id );
	}

	// Rank Math caches sitemap XML; drop it so lastmod picks up the change.
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}

	update_option( 'cs_generated_content_rev', CS_GENERATED_CONTENT_REV, true );
	delete_option( 'cs_generated_content_lock' );
}
add_action( 'init', 'cs_generated_pages_touch', 31 );

/**
 * U1 (Oct 2026): the same one-time date refresh, but only for the
 * generated posts whose text that push changed, so the other combo and
 * game pages keep their honest dates. Bump CS_U1_TOUCH_REV only with a
 * change to cs_u1_touch_targets().
 *  - combo pages with the fastest CPUs (U-04 upgrade advice),
 *  - CPU part pages (U-10 gaming tiers, U-40 "fastest GPU it keeps up
 *    with"), every comparison page (U-10 CPU tiers, U-46 differences
 *    from the shown FPS).
 */
if ( ! defined( 'CS_U1_TOUCH_REV' ) ) {
	define( 'CS_U1_TOUCH_REV', '2026-10-20-u1' );
}

function cs_u1_touch_targets() {
	$top = array();
	if ( function_exists( 'cs_bn_is_top_cpu' ) ) {
		foreach ( cs_get_cpu_data() as $slug => $c ) {
			if ( cs_bn_is_top_cpu( $c ) ) {
				$top[] = $slug;
			}
		}
	}
	$ids = array();
	foreach ( get_posts( array( 'post_type' => array( 'cs_bottleneck', 'cs_hardware', 'cs_comparison' ), 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true ) ) as $p ) {
		if ( 'cs_comparison' === $p->post_type ) {
			$ids[] = $p->ID;
		} elseif ( 'cs_hardware' === $p->post_type ) {
			if ( isset( cs_get_cpu_data()[ $p->post_name ] ) ) {
				$ids[] = $p->ID;
			}
		} else {
			foreach ( $top as $slug ) {
				if ( 0 === strpos( $p->post_name, $slug . '-' ) ) {
					$ids[] = $p->ID;
				}
			}
		}
	}
	return $ids;
}

function cs_u1_pages_touch() {
	if ( wp_installing() || get_option( 'cs_u1_touch_rev' ) === CS_U1_TOUCH_REV ) {
		return;
	}
	if ( ! add_option( 'cs_u1_touch_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_u1_touch_lock' ) > 300 ) {
			delete_option( 'cs_u1_touch_lock' );
		}
		return;
	}
	global $wpdb;
	$ids = cs_u1_touch_targets();
	foreach ( $ids as $id ) {
		$wpdb->update(
			$wpdb->posts,
			array( 'post_modified' => current_time( 'mysql' ), 'post_modified_gmt' => current_time( 'mysql', true ) ),
			array( 'ID' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( $id );
	}
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	if ( function_exists( 'cs_perf_purge_posts' ) ) {
		cs_perf_purge_posts( $ids );
	}
	update_option( 'cs_u1_touch_rev', CS_U1_TOUCH_REV, true );
	delete_option( 'cs_u1_touch_lock' );
}
add_action( 'init', 'cs_u1_pages_touch', 33 );
