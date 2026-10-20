<?php
/**
 * K1 (GSC keyword gap, 20 Oct 2026): the five calculators that already
 * rank around positions 10-20 get the answers their search queries ask
 * for, next to the existing page text:
 *
 * - /storage-calculator/: what fits on 512GB, 1TB, 2TB and 4TB.
 * - /ssd-tbw-calculator/: "What does TBW mean?" and typical TBW ratings.
 * - /ssd-lifespan-calculator/: "How long does an SSD last?" and how to
 *   check SSD health.
 * - /resolution-calculator/: pixels in 4K and a render scale table.
 * - /upload-time-calculator/: H1 "Upload Speed & Time Calculator".
 *
 * Titles and descriptions are in inc/seo-compat.php, the upload speeds and
 * the resolution pixel column in the reference tables in inc/aeo.php.
 * Every number here is either computed from the stated assumption or a
 * manufacturer's published rating (sources under each table).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Data                                                                */
/* ------------------------------------------------------------------ */

/** Share of a drive counted as usable in the storage table (10% left free). */
function cs_k1_usable_share() {
	return 0.9;
}

/**
 * Storage table rows: label, size of one item in GB, unit.
 * Video sizes are Apple's per-minute figures for iPhone HEVC recording
 * (1080p 30 fps about 60MB, 4K 30 fps about 170MB a minute).
 */
function cs_k1_storage_items() {
	return array(
		array( __( 'Photos (4MB each, a typical 12MP phone JPEG)', 'computingskill' ), 0.004, __( 'photos', 'computingskill' ) ),
		array( __( '1080p video, 30 fps (about 3.6GB an hour)', 'computingskill' ), 3.6, __( 'hours', 'computingskill' ) ),
		array( __( '4K video, 30 fps (about 10.2GB an hour)', 'computingskill' ), 10.2, __( 'hours', 'computingskill' ) ),
		array( __( 'Large games (100GB each)', 'computingskill' ), 100, __( 'games', 'computingskill' ) ),
	);
}

function cs_k1_fits( $drive_gb, $item_gb ) {
	return (int) floor( $drive_gb * cs_k1_usable_share() / $item_gb + 1e-9 );
}

/**
 * Manufacturer TBW ratings (5-year warranty on all three lines).
 * null = that capacity is not made.
 */
function cs_k1_tbw_drives() {
	return array(
		array(
			'name'   => 'Samsung 870 EVO',
			'type'   => __( 'SATA, TLC', 'computingskill' ),
			'tbw'    => array( 500 => 300, 1000 => 600, 2000 => 1200, 4000 => 2400 ),
		),
		array(
			'name'   => 'WD_BLACK SN850X',
			'type'   => __( 'NVMe, TLC', 'computingskill' ),
			'tbw'    => array( 500 => null, 1000 => 600, 2000 => 1200, 4000 => 2400 ),
		),
		array(
			'name'   => 'Crucial P3 Plus',
			'type'   => __( 'NVMe, QLC', 'computingskill' ),
			'tbw'    => array( 500 => 110, 1000 => 220, 2000 => 440, 4000 => 800 ),
		),
	);
}

/** Years to reach a TBW rating at a daily write volume. */
function cs_k1_years( $tbw, $gb_per_day ) {
	return $tbw * 1000 / ( $gb_per_day * 365 );
}

/* ------------------------------------------------------------------ */
/* Sections                                                            */
/* ------------------------------------------------------------------ */

function cs_k1_table( $caption, $head, $rows ) {
	$html = '<div class="cs-table-wrap"><table class="cs-hardware-table cs-ref-table cs-k1-table"><caption>' . esc_html( $caption ) . '</caption><thead><tr>';
	foreach ( $head as $h ) {
		$html .= '<th scope="col">' . esc_html( $h ) . '</th>';
	}
	$html .= '</tr></thead><tbody>';
	foreach ( $rows as $row ) {
		$html .= '<tr>';
		foreach ( $row as $i => $cell ) {
			$html .= ( 0 === $i ? '<th scope="row">' : '<td>' ) . esc_html( $cell ) . ( 0 === $i ? '</th>' : '</td>' );
		}
		$html .= '</tr>';
	}
	return $html . '</tbody></table></div>';
}

function cs_k1_link( $slug, $text ) {
	return '<a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . esc_html( $text ) . '</a>';
}

function cs_k1_cap_label( $gb ) {
	return $gb >= 1000 ? ( $gb / 1000 ) . 'TB' : $gb . 'GB';
}

/**
 * Sections per page: 'lead' goes before the page's first H2 (right under
 * the calculator), 'more' before "Related Calculators & Guides".
 */
function cs_k1_sections( $slug ) {
	switch ( $slug ) {
		case 'storage-calculator':
			$caps = array( 512, 1000, 2000, 4000 );
			$rows = array();
			foreach ( cs_k1_storage_items() as $item ) {
				$row = array( $item[0] );
				foreach ( $caps as $cap ) {
					$row[] = cs_aeo_num( cs_k1_fits( $cap, $item[1] ) );
				}
				$rows[] = $row;
			}
			$lead  = '<h2>' . esc_html__( 'What Fits on 512GB, 1TB, 2TB and 4TB?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: photos, 2: hours of 4K video, 3: games */
				__( 'A 1TB drive holds about %1$s phone photos, %2$s hours of 4K video or %3$s large games, keeping 10%% of the drive free. Each line below is one kind of file on an otherwise empty drive; for a mix, enter your own sizes in the calculator above.', 'computingskill' ),
				cs_aeo_num( cs_k1_fits( 1000, 0.004 ) ),
				cs_aeo_num( cs_k1_fits( 1000, 10.2 ) ),
				cs_aeo_num( cs_k1_fits( 1000, 100 ) )
			) ) . '</p>';
			$lead .= cs_k1_table(
				__( 'What fits on a drive (90% of its capacity used)', 'computingskill' ),
				array( __( 'File type', 'computingskill' ), '512GB', '1TB', '2TB', '4TB' ),
				$rows
			);
			$lead .= '<p class="cs-calc-ref-note">' . esc_html__( 'Decimal units, as drive makers count (1TB = 1,000GB). Video sizes are Apple’s per-minute figures for iPhone recording in HEVC; other cameras and codecs differ. Game sizes vary widely: many esports titles need well under 100GB, some big releases more. A system drive also holds Windows and its updates.', 'computingskill' ) . '</p>';
			return array( 'lead' => $lead );

		case 'ssd-tbw-calculator':
			$lead  = '<h2>' . esc_html__( 'What Does TBW Mean?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html__( 'TBW (terabytes written) is the total amount of data a manufacturer rates an SSD to have written to it within its warranty. A 600 TBW rating means 600 terabytes, or 600,000GB, of writes. The warranty lasts until the drive reaches that figure or its warranty years run out, whichever comes first. TBW measures endurance, not speed or capacity.', 'computingskill' ) . '</p>';
			$lead .= '<p>' . esc_html__( 'DWPD (drive writes per day) is the same limit as a daily rate: how many times the full capacity can be written each day of the warranty. The calculator above converts one into the other.', 'computingskill' ) . '</p>';

			$caps = array( 500, 1000, 2000, 4000 );
			$rows = array();
			foreach ( $caps as $cap ) {
				$row = array( cs_k1_cap_label( $cap ) );
				foreach ( cs_k1_tbw_drives() as $d ) {
					$row[] = null === $d['tbw'][ $cap ] ? __( 'not made', 'computingskill' ) : cs_aeo_num( $d['tbw'][ $cap ] ) . ' TBW';
				}
				$rows[] = $row;
			}
			$head = array( __( 'Capacity', 'computingskill' ) );
			foreach ( cs_k1_tbw_drives() as $d ) {
				$head[] = $d['name'] . ' (' . $d['type'] . ')';
			}
			$more  = '<h2>' . esc_html__( 'Typical TBW by Capacity', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html__( 'TBW grows with capacity: a bigger drive spreads the same writes over more flash. Many TLC drives are rated about 600 TBW per terabyte, while QLC drives, which store more bits per cell, are often rated well under half that. Three popular drive lines as examples:', 'computingskill' ) . '</p>';
			$more .= cs_k1_table( __( 'Rated endurance of three SSD lines (5-year warranty)', 'computingskill' ), $head, $rows );
			$more .= '<p class="cs-calc-ref-note">' . esc_html__( 'Ratings as published by Samsung, Western Digital and Crucial for each capacity. Check your exact model and capacity on the maker’s spec sheet, since ratings change between product lines.', 'computingskill' ) . '</p>';
			$more .= '<p>' . sprintf(
				/* translators: %s: link to the SSD Lifespan Calculator */
				esc_html__( 'To see how many years a TBW rating lasts at your own daily writes, use the %s.', 'computingskill' ),
				cs_k1_link( 'ssd-lifespan-calculator', __( 'SSD Lifespan Calculator', 'computingskill' ) )
			) . '</p>';
			return array( 'lead' => $lead, 'more' => $more );

		case 'ssd-lifespan-calculator':
			$lead  = '<h2>' . esc_html__( 'How Long Does an SSD Last?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: years at 50GB/day, 2: years at 20GB/day, 3: years at 100GB/day */
				__( 'For most home use, longer than the PC it is in: the write rating is rarely what ends an SSD. A 1TB drive rated 600 TBW takes about %1$s years to reach that rating at 50GB of writes a day, %2$s years at 20GB a day, and still %3$s years at 100GB a day. The warranty is often 5 years (3 on some budget drives), or until the TBW rating is reached if that comes first.', 'computingskill' ),
				cs_aeo_num( cs_k1_years( 600, 50 ) ),
				cs_aeo_num( cs_k1_years( 600, 20 ) ),
				cs_aeo_num( cs_k1_years( 600, 100 ) )
			) ) . '</p>';
			$lead .= '<p>' . sprintf(
				/* translators: %s: link to the SSD TBW Calculator */
				esc_html__( 'Drives are more often replaced for space or speed, or fail from controller, firmware or power faults that have nothing to do with wear, so keep backups and check the drive’s health now and then (below). A drive lists DWPD instead of TBW? Convert it with the %s.', 'computingskill' ),
				cs_k1_link( 'ssd-tbw-calculator', __( 'SSD TBW Calculator', 'computingskill' ) )
			) . '</p>';

			$more  = '<h2>' . esc_html__( 'How to Check SSD Health', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html__( 'SSDs report their own wear through S.M.A.R.T. data. Any of these shows it:', 'computingskill' ) . '</p><ul>';
			$more .= '<li><strong>' . esc_html__( 'Windows 11 and 10 Settings:', 'computingskill' ) . '</strong> ' . esc_html__( 'System > Storage > Advanced storage settings > Disks & volumes, pick the drive, then Properties. NVMe drives show “Drive health”: estimated remaining life, available spare and temperature.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'CrystalDiskInfo (free, Windows):', 'computingskill' ) . '</strong> ' . esc_html__( 'shows a health status (Good, Caution or Bad) with a percentage, and “Total Host Writes”, which you can compare against the drive’s TBW rating.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'PowerShell (run as administrator):', 'computingskill' ) . '</strong> <code>Get-PhysicalDisk | Select-Object FriendlyName, MediaType, HealthStatus</code> ' . esc_html__( 'for a quick status, and', 'computingskill' ) . ' <code>Get-PhysicalDisk | Get-StorageReliabilityCounter | Select-Object DeviceId, Wear, Temperature, PowerOnHours</code> ' . esc_html__( 'for wear (the share of rated life used, where the drive reports it). The older', 'computingskill' ) . ' <code>wmic diskdrive get model,status</code> ' . esc_html__( 'only says OK or Pred Fail, and WMIC is being removed from Windows 11.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'The maker’s app:', 'computingskill' ) . '</strong> ' . esc_html__( 'Samsung Magician, WD Dashboard or Crucial Storage Executive show health, total writes and firmware updates for their own drives.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'Mac and Linux:', 'computingskill' ) . '</strong> ' . esc_html__( 'System Information > NVMExpress (or SATA) shows the S.M.A.R.T. status on a Mac. On Linux,', 'computingskill' ) . ' <code>sudo smartctl -a /dev/nvme0</code> ' . esc_html__( '(smartmontools) lists “Percentage Used” and “Data Units Written”.', 'computingskill' ) . '</li>';
			$more .= '</ul><p>' . esc_html__( 'To estimate the years left, subtract the total host writes so far from the TBW rating and enter the rest in the calculator above with your daily writes.', 'computingskill' ) . '</p>';
			return array( 'lead' => $lead, 'more' => $more );

		case 'resolution-calculator':
			$mp   = function ( $w, $h ) {
				return cs_aeo_num( $w * $h / 1e6, 2 );
			};
			$lead  = '<h2>' . esc_html__( 'How Many Pixels Is 4K?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: 4K pixels, 2: 4K MP, 3: 1080p pixels, 4: 1080p MP, 5: 1440p MP, 6: DCI 4K MP */
				__( '4K UHD is 3840 × 2160: %1$s pixels, or %2$s megapixels, four times 1080p (1920 × 1080 = %3$s pixels, %4$s MP). 1440p (2560 × 1440) is %5$s MP. Cinema 4K (DCI) is a little wider, 4096 × 2160 or %6$s MP. The table under the calculator lists the other common sizes.', 'computingskill' ),
				cs_aeo_num( 3840 * 2160 ),
				$mp( 3840, 2160 ),
				cs_aeo_num( 1920 * 1080 ),
				$mp( 1920, 1080 ),
				$mp( 2560, 1440 ),
				$mp( 4096, 2160 )
			) ) . '</p>';

			$scales = array( '50%' => 0.5, '58%' => 0.58, '66.7%' => 2 / 3, '75%' => 0.75, '85%' => 0.85 );
			$bases  = array( '1080p' => array( 1920, 1080 ), '1440p' => array( 2560, 1440 ), '4K' => array( 3840, 2160 ) );
			$rows   = array();
			foreach ( $scales as $label => $f ) {
				$row = array( $label );
				foreach ( $bases as $b ) {
					$row[] = round( $b[0] * $f ) . ' × ' . round( $b[1] * $f );
				}
				$row[]  = cs_aeo_num( $f * $f * 100 ) . '%';
				$rows[] = $row;
			}
			$more  = '<h2>' . esc_html__( 'Resolution Scale: What Does 75% of 1440p Render?', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html__( 'A game’s resolution scale (or render scale) slider renders the 3D image at a lower resolution and stretches it to your screen. In most games the percentage applies to both width and height, so 75% of 1440p renders 1920 × 1080, only about 56% of the pixels, which is why it raises FPS so much. Some games count the percentage on total pixels instead; their settings menu usually says which.', 'computingskill' ) . '</p>';
			$more .= cs_k1_table(
				__( 'Render resolution at common scale settings', 'computingskill' ),
				array( __( 'Scale (per axis)', 'computingskill' ), '1080p', '1440p', '4K', __( 'Pixels rendered', 'computingskill' ) ),
				$rows
			);
			$more .= '<p>' . sprintf(
				/* translators: %s: link to the DLSS/FSR guide */
				esc_html__( 'Upscalers use the same idea: DLSS and FSR Quality modes render at about 66.7%% per axis, Balanced at about 58%% and Performance at 50%%, then rebuild the detail. More in %s.', 'computingskill' ),
				cs_k1_link( 'dlss-fsr-frame-generation-explained', __( 'DLSS vs FSR vs XeSS explained', 'computingskill' ) )
			) . '</p>';
			return array( 'lead' => $lead, 'more' => $more );
	}
	return array();
}

/**
 * Extra FAQ items (printed and marked up by inc/extra-faq.php).
 */
function cs_k1_extra_faqs() {
	return array(
		array(
			'slugs' => array( 'storage-calculator' ),
			'q'     => __( 'How many photos can 1TB hold?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: 4MB photos, 2: 10MB photos, 3: 25MB RAW */
				__( 'About %1$s photos of 4MB each (a typical 12MP phone JPEG), keeping 10%% of the drive free. Larger files fit fewer: about %2$s at 10MB each, or %3$s RAW files at 25MB each. Enter your own average size above for an exact count.', 'computingskill' ),
				cs_aeo_num( cs_k1_fits( 1000, 0.004 ) ),
				cs_aeo_num( cs_k1_fits( 1000, 0.010 ) ),
				cs_aeo_num( cs_k1_fits( 1000, 0.025 ) )
			),
		),
		array(
			'slugs' => array( 'storage-calculator' ),
			'q'     => __( 'How many games can a 1TB SSD hold?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: 100GB games, 2: 50GB games */
				__( 'About %1$s games of 100GB, or %2$s of 50GB, keeping 10%% free. Game sizes vary a lot, from well under 50GB for many esports titles to 150GB or more for some big releases, and on a system drive Windows and its updates take their share too.', 'computingskill' ),
				cs_aeo_num( cs_k1_fits( 1000, 100 ) ),
				cs_aeo_num( cs_k1_fits( 1000, 50 ) )
			),
		),
		array(
			'slugs' => array( 'ssd-tbw-calculator' ),
			'q'     => __( 'What is a good TBW for a 1TB SSD?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: %s: years */
				__( '600 TBW is common for 1TB TLC drives such as the Samsung 870 EVO and WD_BLACK SN850X; QLC drives such as the Crucial P3 Plus are rated 220 TBW. Even 220 TBW lasts about %s years of rated writes at 50GB a day, so either is enough for most home use. Pick a higher rating for video editing, virtual machines or other heavy daily writing.', 'computingskill' ),
				cs_aeo_num( cs_k1_years( 220, 50 ) )
			),
		),
		array(
			'slugs' => array( 'ssd-tbw-calculator' ),
			'q'     => __( 'How do I calculate DWPD from TBW?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: %s: DWPD */
				__( 'DWPD = TBW × 1,000 ÷ (capacity in GB × warranty days). A 1TB drive rated 600 TBW over a 5-year warranty is 600,000 ÷ (1,000 × 1,825) = %s DWPD. Choose “A TBW rating” in the calculator above to do it for any drive.', 'computingskill' ),
				cs_aeo_num( 600000 / ( 1000 * 1825 ), 2 )
			),
		),
		array(
			'slugs' => array( 'ssd-lifespan-calculator' ),
			'q'     => __( 'How long does a 1TB SSD last?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: years 600 TBW at 50GB, 2: years 220 TBW at 50GB */
				__( 'At 50GB of writes a day, a 1TB TLC drive rated 600 TBW takes about %1$s years to reach its rating, and a 1TB QLC drive rated 220 TBW about %2$s years. Both are far past a typical 3 to 5-year warranty, so in home use a 1TB SSD is more likely to be replaced for space than worn out.', 'computingskill' ),
				cs_aeo_num( cs_k1_years( 600, 50 ) ),
				cs_aeo_num( cs_k1_years( 220, 50 ) )
			),
		),
	);
}

/**
 * Inserts the sections into the page content. Priority 20: after the
 * shortcodes (11), the reference table and the quick answer box (13).
 */
function cs_k1_page_sections( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || false !== strpos( $content, 'cs-k1-' ) ) {
		return $content;
	}
	$s = cs_k1_sections( (string) get_post_field( 'post_name' ) );
	if ( ! $s ) {
		return $content;
	}
	if ( ! empty( $s['lead'] ) ) {
		$lead = '<div class="cs-k1-section">' . $s['lead'] . '</div>';
		$pos  = strpos( $content, '<h2' );
		$content = false === $pos ? $content . $lead : substr_replace( $content, $lead, $pos, 0 );
	}
	if ( ! empty( $s['more'] ) ) {
		$more = '<div class="cs-k1-section">' . $s['more'] . '</div>';
		if ( preg_match( '#<h2[^>]*>\s*(Related Calculators|Frequently Asked)#', $content, $m, PREG_OFFSET_CAPTURE ) ) {
			$content = substr_replace( $content, $more, $m[0][1], 0 );
		} else {
			$content .= $more;
		}
	}
	return $content;
}
add_filter( 'the_content', 'cs_k1_page_sections', 20 );

/* ------------------------------------------------------------------ */
/* One-time job: upload page H1                                        */
/* ------------------------------------------------------------------ */

if ( ! defined( 'CS_K1_REV' ) ) {
	define( 'CS_K1_REV', '2026-10-20-k1' );
}

/**
 * Renames "Upload Time Calculator" to "Upload Speed & Time Calculator"
 * (only while the title is still the old one, so a hand edit is kept) and
 * purges the hubs that list it. Dates move through CS_CALC_PAGES_REV.
 */
function cs_k1_run() {
	if ( wp_installing() || get_option( 'cs_k1_rev' ) === CS_K1_REV ) {
		return;
	}
	if ( ! add_option( 'cs_k1_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_k1_lock' ) > 300 ) {
			delete_option( 'cs_k1_lock' );
		}
		return;
	}
	global $wpdb;
	$purge = array();
	$page  = get_page_by_path( 'upload-time-calculator' );
	if ( $page && 'Upload Time Calculator' === html_entity_decode( $page->post_title, ENT_QUOTES, 'UTF-8' ) ) {
		$wpdb->update( $wpdb->posts, array( 'post_title' => 'Upload Speed & Time Calculator' ), array( 'ID' => $page->ID ), array( '%s' ), array( '%d' ) );
		clean_post_cache( $page->ID );
		$purge[] = $page->ID;
	}
	foreach ( array( 'internet-tools', 'computer-tools', 'download-time-calculator', 'file-transfer-time-calculator', 'bandwidth-calculator' ) as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$purge[] = $p->ID;
		}
	}
	if ( function_exists( 'cs_perf_purge_posts' ) ) {
		cs_perf_purge_posts( $purge );
	}
	update_option( 'cs_k1_rev', CS_K1_REV, true );
	delete_option( 'cs_k1_lock' );
}
add_action( 'init', 'cs_k1_run', 43 );
