<?php
/**
 * Blog posts (Oct 2026, ranking-readiness item 5).
 *
 * The site had no WordPress posts at all: /blog/ is a hub of the existing
 * calculators and guides. This file adds what posts need around them:
 *  - a "Latest Articles" block for /blog/ and the homepage (prints nothing
 *    until a post is published);
 *  - a "Tools for this article" box under each post, picked from the post's
 *    own title and text;
 *  - posts in the Rank Math sitemap regardless of the admin toggle;
 *  - one-time creation of DRAFT posts from data/blog-drafts/*.php. Drafts
 *    are never published or overwritten by code: Ali reviews each one in
 *    wp-admin and publishes it himself.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Card grid of the newest published posts, or '' when there are none.
 */
function cs_latest_posts_section( $count = 6, $title = '' ) {
	$q = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	if ( ! $q->have_posts() ) {
		return '';
	}
	ob_start();
	?>
	<section class="cs-home-section cs-latest-posts">
		<h2><?php echo esc_html( $title ? $title : __( 'Latest Articles', 'computingskill' ) ); ?></h2>
		<div class="cs-card-grid">
			<?php
			while ( $q->have_posts() ) :
				$q->the_post();
				?>
				<a class="cs-card cs-blog-card" href="<?php the_permalink(); ?>">
					<span class="cs-blog-card-tag"><?php echo esc_html( get_the_date() ); ?></span>
					<span class="cs-card-title"><?php the_title(); ?></span>
					<span class="cs-card-desc"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></span>
				</a>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Calculator links for a post, matched on words in its title and text.
 * Order is the order of the map; at most five links.
 */
function cs_post_tool_links( $post_id ) {
	$text = strtolower( get_the_title( $post_id ) . ' ' . wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	$map  = array(
		'bottleneck'  => 'pc-bottleneck-calculator',
		'fps'         => 'fps-calculator',
		'psu'         => 'psu-calculator',
		'power supply' => 'psu-calculator',
		'vram'        => 'gpu-vram-calculator',
		'ram'         => 'ram-calculator',
		'upgrade'     => 'pc-upgrade-calculator',
		'compatib'    => 'pc-compatibility-checker',
		'electricity' => 'pc-power-consumption-calculator',
		'storage'     => 'storage-calculator',
	);
	$links = array();
	foreach ( $map as $word => $slug ) {
		$end = 'compatib' === $word ? '' : '\b';
		if ( preg_match( '/\b' . preg_quote( $word, '/' ) . $end . '/', $text ) && ! in_array( $slug, $links, true ) ) {
			$links[] = $slug;
		}
	}
	if ( ! $links ) {
		$links = array( 'pc-bottleneck-calculator', 'fps-calculator' );
	}
	return array_slice( $links, 0, 5 );
}

/**
 * Posts in the sitemap even if Rank Math's "Posts" toggle is off.
 */
function cs_sitemap_include_posts( $post_types ) {
	$post_types['post'] = 'post';
	return $post_types;
}
add_filter( 'rank_math/sitemap/post_types', 'cs_sitemap_include_posts', 20 );

/**
 * Creates each draft in data/blog-drafts/ once. Each file returns an array:
 * slug, title, excerpt, category, content (HTML for the block editor).
 * The created slugs are remembered, so a draft Ali deletes or renames is
 * not recreated. Editing a draft file here updates the WordPress draft only
 * while that draft is untouched (see the hash check below).
 */
function cs_blog_drafts_sync() {
	// E1 (PERF-02): not on admin-ajax (the contact form's anonymous nonce
	// fetch runs admin_init too) or for visitors who aren't logged in.
	if ( wp_installing() || ! is_admin() || wp_doing_ajax() || ! is_user_logged_in() ) {
		return;
	}
	$files = glob( CS_THEME_DIR . '/data/blog-drafts/*.php' );
	if ( ! $files ) {
		return;
	}
	$done = get_option( 'cs_blog_drafts_created', array() );
	if ( ! is_array( $done ) ) {
		$done = array();
	}

	$author = 0;
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	if ( $admins ) {
		$author = (int) $admins[0];
	}

	$changed = false;
	foreach ( $files as $file ) {
		$d = include $file;
		if ( ! is_array( $d ) || empty( $d['slug'] ) ) {
			continue;
		}
		$hash = md5( $d['title'] . $d['excerpt'] . $d['content'] );
		if ( isset( $done[ $d['slug'] ] ) ) {
			// A newer version of a draft we created replaces it only while
			// it is still a draft nobody has edited (its content still
			// matches what we wrote). Anything Ali touched is left alone.
			$entry = $done[ $d['slug'] ];
			$id    = is_array( $entry ) ? (int) $entry['id'] : (int) $entry;
			$old   = is_array( $entry ) ? $entry['hash'] : '';
			$post  = $id ? get_post( $id ) : null;
			// Entries from theme 1.0.36 stored only the ID; for those, an
			// untouched draft still has its modified time equal to its
			// creation time.
			$untouched = $post && ( $old
				? md5( $post->post_title . $post->post_excerpt . $post->post_content ) === $old
				: $post->post_modified === $post->post_date );
			if ( $old !== $hash && $untouched && 'draft' === $post->post_status ) {
				$r = wp_update_post( array(
					'ID'           => $id,
					'post_title'   => $d['title'],
					'post_excerpt' => $d['excerpt'],
					'post_content' => $d['content'],
				), true );
				if ( ! is_wp_error( $r ) ) {
					$done[ $d['slug'] ] = array( 'id' => $id, 'hash' => $hash );
					$changed            = true;
				}
			}
			continue;
		}
		$existing = get_posts( array(
			'name'           => $d['slug'],
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );
		if ( $existing ) {
			$done[ $d['slug'] ] = (int) $existing[0];
			$changed            = true;
			continue;
		}
		$cat = 0;
		if ( ! empty( $d['category'] ) ) {
			$term = term_exists( $d['category'], 'category' );
			if ( ! $term ) {
				$term = wp_insert_term( $d['category'], 'category' );
			}
			if ( ! is_wp_error( $term ) ) {
				$cat = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}
		$id = wp_insert_post( array(
			'post_type'      => 'post',
			'post_status'    => 'draft',
			'post_name'      => $d['slug'],
			'post_title'     => $d['title'],
			'post_excerpt'   => $d['excerpt'],
			'post_content'   => $d['content'],
			'post_author'    => $author,
			'post_category'  => $cat ? array( $cat ) : array(),
			'comment_status' => 'closed',
		), true );
		if ( ! is_wp_error( $id ) ) {
			$done[ $d['slug'] ] = array( 'id' => (int) $id, 'hash' => $hash );
			$changed            = true;
		}
	}
	if ( $changed ) {
		update_option( 'cs_blog_drafts_created', $done, false );
	}
}
add_action( 'admin_init', 'cs_blog_drafts_sync' );
