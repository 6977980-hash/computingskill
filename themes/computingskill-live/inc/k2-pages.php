<?php
/**
 * K2 (GSC keyword gap, 20 Oct 2026): /vram/ answers what its searches ask
 * for ("vram vs ram", "how to check vram", "dedicated vs shared gpu
 * memory", "is vram faster than ram"), next to the existing explainer:
 *
 * - H2 "VRAM vs RAM" with a comparison table.
 * - H2 "Dedicated vs Shared GPU Memory".
 * - H2 "How to Check How Much VRAM You Have".
 * - Four FAQ items (printed and marked up by inc/extra-faq.php).
 *
 * The opening sentence swap is in inc/calculator-pages.php, the title and
 * description in inc/seo-compat.php. Bandwidth figures are computed from
 * the bus width and data rate in the manufacturers' specifications.
 * Tables and links reuse the K1 helpers (inc/k1-pages.php).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Data                                                                */
/* ------------------------------------------------------------------ */

/** Peak bandwidth in GB/s: bus width in bits × data rate in Gbps (or GT/s) ÷ 8. */
function cs_k2_bandwidth( $bus_bits, $gbps ) {
	return $bus_bits * $gbps / 8;
}

/**
 * Example memory setups: label, bus width (bits), data rate (Gbps per pin).
 * Graphics cards from NVIDIA's specifications; system RAM is two 64-bit
 * channels at the module's rated MT/s.
 */
function cs_k2_bandwidth_examples() {
	return array(
		'rtx4060'  => array( __( 'GeForce RTX 4060 (8GB GDDR6, 128-bit, 17 Gbps)', 'computingskill' ), 128, 17 ),
		'rtx4070'  => array( __( 'GeForce RTX 4070 (12GB GDDR6X, 192-bit, 21 Gbps)', 'computingskill' ), 192, 21 ),
		'rtx4090'  => array( __( 'GeForce RTX 4090 (24GB GDDR6X, 384-bit, 21 Gbps)', 'computingskill' ), 384, 21 ),
		'ddr4'     => array( __( 'DDR4-3200, dual channel (system RAM)', 'computingskill' ), 128, 3.2 ),
		'ddr5'     => array( __( 'DDR5-6000, dual channel (system RAM)', 'computingskill' ), 128, 6 ),
		'pcie4x16' => array( __( 'PCIe 4.0 x16 link, one direction (16 lanes at 16 GT/s, 128b/130b encoding)', 'computingskill' ), 16, 15.754 ),
	);
}

function cs_k2_bw( $key ) {
	$e = cs_k2_bandwidth_examples()[ $key ];
	return cs_k2_bandwidth( $e[1], $e[2] );
}

function cs_k2_gbs( $value ) {
	$d = ( $value < 100 && abs( $value - round( $value ) ) > 0.05 ) ? 1 : 0;
	return cs_aeo_num( $value, $d ) . ' GB/s';
}

/* ------------------------------------------------------------------ */
/* Sections                                                            */
/* ------------------------------------------------------------------ */

function cs_k2_vram_sections() {
	if ( ! function_exists( 'cs_k1_table' ) || ! function_exists( 'cs_aeo_num' ) ) {
		return '';
	}

	// VRAM vs RAM.
	$html  = '<h2>' . esc_html__( 'VRAM vs RAM', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html( sprintf(
		/* translators: 1: RTX 4070 bandwidth, 2: DDR5 bandwidth */
		__( 'Both are memory chips, but they sit in different places and do different jobs. RAM (system memory) is the CPU’s working space for Windows, apps and a game’s own logic. VRAM is the graphics card’s private memory for what the GPU draws. VRAM is built for bandwidth: an RTX 4070 can move about %1$s, while dual-channel DDR5-6000 system RAM peaks at about %2$s. More RAM does not add VRAM, and more VRAM does not help a system that is short of RAM.', 'computingskill' ),
		cs_k2_gbs( cs_k2_bw( 'rtx4070' ) ),
		cs_k2_gbs( cs_k2_bw( 'ddr5' ) )
	) ) . '</p>';
	$html .= cs_k1_table(
		__( 'VRAM vs system RAM at a glance', 'computingskill' ),
		array( '', __( 'VRAM (video memory)', 'computingskill' ), __( 'RAM (system memory)', 'computingskill' ) ),
		array(
			array( __( 'Where it sits', 'computingskill' ), __( 'On the graphics card, soldered around the GPU chip', 'computingskill' ), __( 'In the motherboard’s memory slots (soldered on many laptops)', 'computingskill' ) ),
			array( __( 'Used by', 'computingskill' ), __( 'The GPU', 'computingskill' ), __( 'The CPU, Windows and every running program; integrated graphics borrow from it too', 'computingskill' ) ),
			array( __( 'Holds', 'computingskill' ), __( 'Textures, frame buffers, geometry, shadow maps, ray tracing data', 'computingskill' ), __( 'Game code and world state, the operating system, apps, browser tabs', 'computingskill' ) ),
			array( __( 'Memory type', 'computingskill' ), __( 'GDDR6, GDDR6X or GDDR7 (HBM on some data-center cards)', 'computingskill' ), __( 'DDR4 or DDR5 (LPDDR in many laptops)', 'computingskill' ) ),
			array( __( 'Peak bandwidth', 'computingskill' ), sprintf( __( 'About %1$s (RTX 4060) to %2$s (RTX 4090)', 'computingskill' ), cs_k2_gbs( cs_k2_bw( 'rtx4060' ) ), cs_k2_gbs( cs_k2_bw( 'rtx4090' ) ) ), sprintf( __( 'About %1$s (DDR4-3200) to %2$s (DDR5-6000), dual channel', 'computingskill' ), cs_k2_gbs( cs_k2_bw( 'ddr4' ) ), cs_k2_gbs( cs_k2_bw( 'ddr5' ) ) ) ),
			array( __( 'Typical size (gaming PC)', 'computingskill' ), __( '8GB to 16GB; 24GB to 32GB on flagship cards', 'computingskill' ), __( '16GB to 32GB', 'computingskill' ) ),
			array( __( 'Can you add more?', 'computingskill' ), __( 'No, only by changing the graphics card', 'computingskill' ), __( 'Yes on most desktops, if a slot or a larger kit fits', 'computingskill' ) ),
			array( __( 'When it runs out', 'computingskill' ), __( 'Stutter, texture pop-in or lower texture quality', 'computingskill' ), __( 'Slowdowns while Windows swaps to the page file', 'computingskill' ) ),
		)
	);
	$html .= '<p class="cs-calc-ref-note">' . esc_html__( 'Peak bandwidth = bus width × data rate ÷ 8, from the manufacturers’ specifications. System RAM answers small, scattered requests sooner (lower latency), which suits the CPU; GDDR trades some latency for far more data per second, which suits a GPU reading whole textures.', 'computingskill' ) . '</p>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link to the RAM explainer */
		esc_html__( '%s explains how system memory works and which RAM specs matter.', 'computingskill' ),
		cs_k1_link( 'ram', __( 'What Is RAM?', 'computingskill' ) )
	) . '</p>';

	// Dedicated vs shared.
	$html .= '<h2>' . esc_html__( 'Dedicated vs Shared GPU Memory', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Windows Task Manager lists two kinds of GPU memory, and only one of them is VRAM:', 'computingskill' ) . '</p><ul>';
	$html .= '<li><strong>' . esc_html__( 'Dedicated GPU memory', 'computingskill' ) . '</strong> ' . esc_html__( 'is the VRAM on the graphics card. This is the number that matters for games and the one listed in a card’s specifications.', 'computingskill' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Shared GPU memory', 'computingskill' ) . '</strong> ' . esc_html( sprintf(
		/* translators: %s: PCIe 4.0 x16 bandwidth */
		__( 'is system RAM that Windows lets the GPU borrow when its own memory is full, by default up to half of the RAM installed. It is not set aside: programs keep using it until the GPU needs it. A graphics card reaches it over the PCIe slot, about %s on a PCIe 4.0 x16 link, a small fraction of its own VRAM bandwidth, so a game that spills into shared memory keeps running but stutters.', 'computingskill' ),
		cs_k2_gbs( cs_k2_bw( 'pcie4x16' ) )
	) ) . '</li>';
	$html .= '</ul><p>' . esc_html__( 'Integrated graphics (built into Intel and AMD processors) have no VRAM of their own and use shared system memory for everything. Many BIOS setups let you reserve a fixed slice of RAM for them (often called UMA Frame Buffer Size or iGPU memory), which then shows as dedicated memory, but it is still system RAM at system RAM speed. Apple silicon Macs work the same way with unified memory that the CPU and GPU share.', 'computingskill' ) . '</p>';

	// How to check.
	$html .= '<h2>' . esc_html__( 'How to Check How Much VRAM You Have', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Any of these shows your graphics card’s VRAM. The first is the quickest on Windows 10 and 11:', 'computingskill' ) . '</p><ol>';
	$html .= '<li><strong>' . esc_html__( 'Task Manager:', 'computingskill' ) . '</strong> ' . esc_html__( 'press Ctrl + Shift + Esc, open Performance and select GPU (GPU 1 on a laptop with two GPUs is usually the graphics card). “Dedicated GPU memory” is your VRAM and how much of it is in use right now; “Shared GPU memory” is borrowed system RAM.', 'computingskill' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'DirectX Diagnostic Tool:', 'computingskill' ) . '</strong> ' . esc_html__( 'press Windows + R, type dxdiag and press Enter, then open the Display tab. “Dedicated Memory” is the VRAM; “Display Memory” adds the shared memory on top, so it reads higher.', 'computingskill' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Windows Settings:', 'computingskill' ) . '</strong> ' . esc_html__( 'System > Display > Advanced display (Advanced display settings on Windows 10), then “Display adapter properties”. The Adapter tab shows “Dedicated Video Memory”.', 'computingskill' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'GPU-Z (free, by TechPowerUp):', 'computingskill' ) . '</strong> ' . esc_html__( 'the Graphics Card tab shows memory size, type (GDDR6, GDDR6X…) and bus width; the Sensors tab shows memory used while a game runs.', 'computingskill' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'NVIDIA and AMD software:', 'computingskill' ) . '</strong> ' . esc_html__( 'NVIDIA Control Panel > System Information (bottom left) lists “Dedicated video memory”, and', 'computingskill' ) . ' <code>nvidia-smi</code> ' . esc_html__( 'in a command prompt shows total and used memory. In AMD Software: Adrenalin Edition, the System section of Settings lists the card’s memory size and type.', 'computingskill' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Mac and Linux:', 'computingskill' ) . '</strong> ' . esc_html__( 'on a Mac, Apple menu > About This Mac > More Info > System Report > Graphics/Displays shows VRAM on Intel Macs with a graphics card (Apple silicon Macs use unified memory instead). On Linux,', 'computingskill' ) . ' <code>glxinfo -B</code> ' . esc_html__( 'lists “Video memory”, or use', 'computingskill' ) . ' <code>nvidia-smi</code> ' . esc_html__( 'on NVIDIA cards.', 'computingskill' ) . '</li>';
	$html .= '</ol><p>' . esc_html__( 'Many games also show an estimate of how much VRAM your chosen settings will use in their graphics menu. If that estimate, or Task Manager’s dedicated memory while you play, sits at the card’s limit, lower texture quality first.', 'computingskill' ) . '</p>';
	$html .= '<p>' . sprintf(
		/* translators: 1: link to How Much VRAM Do I Need, 2: link to the GPU VRAM Calculator */
		esc_html__( 'Not sure whether your amount is enough? See %1$s for each resolution, or enter your resolution and settings in the %2$s.', 'computingskill' ),
		cs_k1_link( 'how-much-vram-do-i-need', __( 'How Much VRAM Do I Need?', 'computingskill' ) ),
		cs_k1_link( 'gpu-vram-calculator', __( 'GPU VRAM Calculator', 'computingskill' ) )
	) . '</p>';

	return $html;
}

/**
 * Extra FAQ items (merged into cs_extra_faq_definitions()).
 */
function cs_k2_extra_faqs() {
	if ( ! function_exists( 'cs_aeo_num' ) ) {
		return array();
	}
	return array(
		array(
			'slugs' => array( 'vram' ),
			'q'     => __( 'What does VRAM stand for?', 'computingskill' ),
			'a'     => __( 'VRAM stands for video random access memory, usually shortened to video RAM or video memory. It is the memory on a graphics card that only the GPU uses, separate from the system RAM the CPU uses.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'vram' ),
			'q'     => __( 'Is VRAM faster than RAM?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: RTX 4070 bandwidth, 2: DDR5 bandwidth, 3: ratio */
				__( 'In bandwidth, yes, by several times: an RTX 4070’s 12GB of GDDR6X moves about %1$s, about %3$s times the %2$s that dual-channel DDR5-6000 system RAM peaks at. System RAM has lower latency for small requests, which is what the CPU needs. The two are built for different jobs and cannot stand in for each other.', 'computingskill' ),
				cs_k2_gbs( cs_k2_bw( 'rtx4070' ) ),
				cs_k2_gbs( cs_k2_bw( 'ddr5' ) ),
				cs_aeo_num( cs_k2_bw( 'rtx4070' ) / cs_k2_bw( 'ddr5' ), 1 )
			),
		),
		array(
			'slugs' => array( 'vram' ),
			'q'     => __( 'What uses VRAM?', 'computingskill' ),
			'a'     => __( 'In games, textures take the largest share, followed by the frame buffers (which grow with resolution), geometry, shadow maps, and ray tracing and frame generation data. That is why texture quality, resolution and ray tracing are the settings that move VRAM use the most. Outside games, video editing, 3D rendering and local AI models also use VRAM, and an AI model generally has to fit in it to run at full speed. Windows and your browser use a small amount as well.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'vram' ),
			'q'     => __( 'Is shared GPU memory the same as VRAM?', 'computingskill' ),
			'a'     => __( 'No. Shared GPU memory is system RAM that Windows lets the GPU borrow when its own VRAM is full, by default up to half of the installed RAM. It is much slower for a graphics card to reach, so it keeps a game running instead of crashing, but with stutter. Nothing in Windows adds VRAM to a graphics card, and reserving more RAM for integrated graphics in the BIOS does not make that memory any faster.', 'computingskill' ),
		),
	);
}

/**
 * Inserts the sections before "Key Specs to Look At…", the explainer's
 * second H2 (after "How VRAM Works"). Falls back to before "Related
 * Calculators & Guides", then the end. Priority 20, like K1.
 */
function cs_k2_page_sections( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || 'vram' !== get_post_field( 'post_name' ) || false !== strpos( $content, 'cs-k2-' ) ) {
		return $content;
	}
	$html = cs_k2_vram_sections();
	if ( ! $html ) {
		return $content;
	}
	$html = '<div class="cs-k1-section cs-k2-section">' . $html . '</div>';
	if ( preg_match( '#<h2[^>]*>\s*(Key Specs|Related Calculators|Frequently Asked)#', $content, $m, PREG_OFFSET_CAPTURE ) ) {
		return substr_replace( $content, $html, $m[0][1], 0 );
	}
	return $content . $html;
}
add_filter( 'the_content', 'cs_k2_page_sections', 20 );
