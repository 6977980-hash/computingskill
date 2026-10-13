<?php
/**
 * Draft post. Wattages use the PSU Calculator formula in
 * assets/js/calculators.js (CS_DEFS.psu) with data/hardware-data.php as of
 * theme 1.0.36: CPU max power + GPU board power + 2x5W RAM + 2x6W drives +
 * 3x3W fans + 50W board, x1.3, rounded up to 50W, never below the GPU
 * maker's figure.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return array(
	'slug'     => 'what-psu-do-i-need-for-rtx-5070',
	'title'    => 'What PSU Do I Need for an RTX 5070? (650W, 750W or 850W)',
	'category' => 'Buying Guides',
	'excerpt'  => 'NVIDIA recommends 650W for the RTX 5070, but your CPU changes the answer. Estimated system draw and the PSU size we recommend for 19 CPUs, plus what to look for in the unit.',
	'content'  => <<<'HTML'
<p>NVIDIA's own recommendation for the GeForce RTX 5070 is a <strong>650W</strong> power supply, with a graphics card power rating of 250W (<a href="https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5070-family/" rel="noopener" target="_blank">NVIDIA specifications</a>). That figure assumes a typical system. If your CPU pulls a lot of power at full turbo, you need more.</p>

<h2>Quick answer</h2>
<ul>
<li><strong>650W</strong> is enough for the RTX 5070 with most mainstream CPUs: Ryzen 5 5600 to Ryzen 7 9800X3D on AMD, and Core i3-12100F, i5-12400F, i5-13400F and Core Ultra 5 245K on Intel.</li>
<li><strong>700W</strong> for the Core i5-13600K or i5-14600K.</li>
<li><strong>750W</strong> for the Ryzen 9 7900X.</li>
<li><strong>800W</strong> for the Core i7-13700K, i7-14700K, i9-13900K or Core Ultra 9 285K. Since 800W units are less common, an 850W unit is the usual buy.</li>
</ul>
<p>Add roughly 15% if you plan to overclock: our <a href="/psu-calculator/">PSU Calculator</a> handles that with one checkbox.</p>

<h2>How we calculate it</h2>
<p>Our PSU Calculator adds up the parts at their peak, not their average:</p>
<ol>
<li>The CPU at its maximum turbo or boost power (Intel's "Maximum Turbo Power", AMD's PPT), not its base TDP.</li>
<li>The graphics card's board power (250W for the RTX 5070).</li>
<li>5W per RAM stick, 6W per drive, 3W per fan and 50W for the motherboard and the rest. The table below assumes 2 sticks, 2 drives and 3 fans.</li>
<li>30% headroom on top, rounded up to the next 50W, and never below the GPU maker's own figure.</li>
</ol>
<p>The headroom covers short power spikes from the graphics card and keeps the PSU in its efficient load range rather than running near its limit.</p>

<h2>RTX 5070 PSU size by CPU</h2>
<table>
<thead><tr><th>Processor</th><th>CPU max power</th><th>Estimated system draw</th><th>Recommended PSU</th></tr></thead>
<tbody>
<tr><td>AMD Ryzen 5 5600 / 5600X, Ryzen 7 5700X</td><td>88W</td><td>419W</td><td>650W</td></tr>
<tr><td>AMD Ryzen 5 7600, Ryzen 7 9700X</td><td>88W</td><td>419W</td><td>650W</td></tr>
<tr><td>Intel Core i3-12100F</td><td>89W</td><td>420W</td><td>650W</td></tr>
<tr><td>Intel Core i5-12400F</td><td>117W</td><td>448W</td><td>650W</td></tr>
<tr><td>AMD Ryzen 5 7600X, Ryzen 7 7700X</td><td>142W</td><td>473W</td><td>650W</td></tr>
<tr><td>Intel Core i5-13400F</td><td>148W</td><td>479W</td><td>650W</td></tr>
<tr><td>Intel Core Ultra 5 245K</td><td>159W</td><td>490W</td><td>650W</td></tr>
<tr><td>AMD Ryzen 7 9800X3D</td><td>162W</td><td>493W</td><td>650W</td></tr>
<tr><td>Intel Core i5-13600K / i5-14600K</td><td>181W</td><td>512W</td><td>700W</td></tr>
<tr><td>AMD Ryzen 9 7900X</td><td>230W</td><td>561W</td><td>750W</td></tr>
<tr><td>Intel Core Ultra 9 285K</td><td>250W</td><td>581W</td><td>800W</td></tr>
<tr><td>Intel Core i7-13700K / i7-14700K, i9-13900K</td><td>253W</td><td>584W</td><td>800W</td></tr>
</tbody>
</table>
<p>"Estimated system draw" is the peak with every part at full load at once, which is rare in games; typical gaming draw is lower. Our <a href="/pc-power-consumption-calculator/">PC Power Consumption Calculator</a> estimates everyday draw and electricity cost instead.</p>

<h2>What to look for in the power supply</h2>
<ul>
<li><strong>A reputable brand and 80+ Bronze or better.</strong> Efficiency ratings tell you how much power is wasted as heat, and better units usually have better protection circuits too.</li>
<li><strong>The right power connector.</strong> The RTX 5070 Founders Edition and many partner cards use the 12V-2x6 (16-pin) connector, while some partner cards use standard 8-pin plugs, so check your card's spec page. For a 16-pin card, a PSU with a native 12V-2x6 cable avoids adapters; otherwise use the adapter that comes with the card and push it fully in.</li>
<li><strong>Room to upgrade.</strong> If you might move to an RTX 5070 Ti or faster later, buying 750W to 850W now saves replacing the PSU twice. NVIDIA recommends 750W for the RTX 5070 Ti.</li>
</ul>
<p>More detail on ratings, connectors and sizing is in our <a href="/how-to-choose-a-psu/">How to Choose a PSU</a> guide.</p>

[cs_faq]
[cs_faq_item q="Is a 650W PSU enough for an RTX 5070?"]Yes for most builds. NVIDIA recommends 650W, and our PSU Calculator also lands on 650W with CPUs up to the Ryzen 7 9800X3D, Core i5-13400F and Core Ultra 5 245K. High-power Intel K-series chips and the Ryzen 9 7900X need 700W to 800W.[/cs_faq_item]
[cs_faq_item q="What PSU do I need for an RTX 5070 and Ryzen 7 9800X3D?"]650W. With the 9800X3D at its 162W boost power, our PSU Calculator estimates a 493W peak for the whole system, and 30% headroom rounds to 650W, the same as NVIDIA's recommendation.[/cs_faq_item]
[cs_faq_item q="Is a 550W PSU enough for an RTX 5070?"]We do not recommend it. 550W is below NVIDIA's own 650W recommendation for the RTX 5070, even if a low-power CPU keeps the estimated peak around 420W.[/cs_faq_item]
[cs_faq_item q="Do I need an 850W PSU for an RTX 5070?"]Only with a high-power CPU such as the Core i7-14700K or i9-13900K (our formula gives 800W, and 850W is the common size above that), if you overclock, or if you plan a bigger GPU upgrade later.[/cs_faq_item]
[/cs_faq]

<p><em>Wattages are estimates from ComputingSkill's PSU Calculator using manufacturer power figures. Always follow your graphics card and PSU makers' own guidance.</em></p>
HTML
);
