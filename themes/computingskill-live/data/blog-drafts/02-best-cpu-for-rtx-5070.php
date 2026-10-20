<?php
/**
 * Draft post (K4, 20 Oct 2026). Rebuilt in K4 with the D3/D4 engine and all 34 CPUs.
 * Generated from cs_calculate_bottleneck(), cs_tl_fps() and cs_aeo_psu()
 * with data/hardware-data.php as of theme 1.0.56; regenerate if scores change.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return array(
	'slug'     => 'best-cpu-for-rtx-5070',
	'title'    => 'Best CPU for RTX 5070: Which Processor Won’t Hold It Back?',
	'category' => 'Buying Guides',
	'excerpt'  => 'We paired the GeForce RTX 5070 with 34 CPUs in our bottleneck engine. Here is which processors keep it busy at 1080p and 1440p, and what power supply each build needs.',
	'content'  => <<<'HTML'
<p>NVIDIA's GeForce RTX 5070 is a 12GB GDDR7, 250W card aimed at 1440p, and NVIDIA lists 650W as the required system power (<a href="https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5070-family/" rel="noopener" target="_blank">NVIDIA specifications</a>). A card in this class can outrun an older or budget processor, especially at 1080p. So which CPU is enough?</p>
<p>We paired the RTX 5070 with all 34 processors in our database, using the engine behind our <a href="/pc-bottleneck-calculator/">PC Bottleneck Calculator</a> and the formula of our <a href="/psu-calculator/">PSU Calculator</a>. These are specs-based estimates, not benchmarks.</p>

<h2>Quick answer</h2>
<ul>
<li><strong>Enough at 1080p:</strong> any CPU from the Ryzen 7 7700X (gaming score 73) up keeps the RTX 5070 fully busy at 1080p. That is 13 of the 34 processors we tested.</li>
<li><strong>Close enough (mild, under 15%):</strong> Ryzen 7 5700X3D (14%), Core i7-12700K (14%), Ryzen 5 7600 (12%), Core Ultra 5 245K (11%), Ryzen 5 7600X (10%), Ryzen 7 5800X3D (8%), Core i5-13600K (8%), Ryzen 5 9600X (6%), Ryzen 9 7900X (6%) and Core i5-14600K (6%).</li>
<li><strong>Holds it back at 1080p (15% or more):</strong> Ryzen 5 3600 (45%), Core i3-12100F (37%), Ryzen 5 5600 (32%), Ryzen 5 5600X (29%), Ryzen 7 5700X (28%), Core i5-12400F (28%), Ryzen 7 5800X (25%), Core i5-13400F (23%), Core i5-14400F (22%), Core i5-12600K (19%) and Ryzen 5 7500F (15%).</li>
<li><strong>At 1440p and 4K:</strong> only the Ryzen 5 3600 and Core i3-12100F still limit the card at 1440p; at 4K every CPU we tested leaves the RTX 5070 as the limit.</li>
</ul>

<h2>RTX 5070 with 34 processors</h2>
<p>How to read the table: <strong>CPU %</strong> is the share of the graphics card left idle because the processor can't feed it (6-14% mild, 15-29% noticeable, 30% and up severe). <strong>Balanced</strong> means the two are within 6% of each other. <strong>GPU-limited</strong> is the normal, healthy result: the graphics card is the part working flat out. FPS is our estimate for a typical AAA game at high settings. The full method is on our <a href="/methodology/">methodology page</a>.</p>
<table>
<thead><tr><th>Processor</th><th>Socket</th><th>1080p</th><th>1440p</th><th>4K</th><th>FPS at 1080p</th><th>PSU</th></tr></thead>
<tbody>
<tr><td><a href="/hardware/ryzen-7-9800x3d/">Ryzen 7 9800X3D</a></td><td>AM5</td><td>GPU-limited</td><td>GPU-limited</td><td>GPU-limited</td><td>163</td><td>650W</td></tr>
<tr><td><a href="/hardware/ryzen-9-9950x3d/">Ryzen 9 9950X3D</a></td><td>AM5</td><td>GPU-limited</td><td>GPU-limited</td><td>GPU-limited</td><td>163</td><td>750W</td></tr>
<tr><td><a href="/bottleneck/ryzen-7-7800x3d-rtx-5070/">Ryzen 7 7800X3D</a></td><td>AM5</td><td>GPU-limited</td><td>GPU-limited</td><td>GPU-limited</td><td>163</td><td>650W</td></tr>
<tr><td><a href="/hardware/core-i9-14900k/">Core i9-14900K</a></td><td>LGA 1700</td><td>GPU-limited</td><td>GPU-limited</td><td>GPU-limited</td><td>163</td><td>800W</td></tr>
<tr><td><a href="/bottleneck/ryzen-5-7600x3d-rtx-5070/">Ryzen 5 7600X3D</a></td><td>AM5</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>163</td><td>650W</td></tr>
<tr><td><a href="/hardware/core-i9-13900k/">Core i9-13900K</a></td><td>LGA 1700</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>163</td><td>800W</td></tr>
<tr><td><a href="/hardware/core-i7-14700k/">Core i7-14700K</a></td><td>LGA 1700</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>163</td><td>800W</td></tr>
<tr><td><a href="/hardware/core-ultra-9-285k/">Core Ultra 9 285K</a></td><td>LGA 1851</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>163</td><td>800W</td></tr>
<tr><td><a href="/hardware/core-i7-13700k/">Core i7-13700K</a></td><td>LGA 1700</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>159</td><td>800W</td></tr>
<tr><td><a href="/hardware/core-ultra-7-265k/">Core Ultra 7 265K</a></td><td>LGA 1851</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>159</td><td>800W</td></tr>
<tr><td><a href="/hardware/ryzen-9-9900x/">Ryzen 9 9900X</a></td><td>AM5</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>159</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/ryzen-7-7700x-rtx-5070/">Ryzen 7 9700X</a></td><td>AM5</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>159</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/ryzen-7-7700x-rtx-5070/">Ryzen 7 7700X</a></td><td>AM5</td><td>Balanced</td><td>GPU-limited</td><td>GPU-limited</td><td>155</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/core-i5-14600k-rtx-5070/">Core i5-14600K</a></td><td>LGA 1700</td><td>CPU 6%</td><td>GPU-limited</td><td>GPU-limited</td><td>153</td><td>700W</td></tr>
<tr><td><a href="/hardware/ryzen-9-7900x/">Ryzen 9 7900X</a></td><td>AM5</td><td>CPU 6%</td><td>GPU-limited</td><td>GPU-limited</td><td>153</td><td>750W</td></tr>
<tr><td><a href="/bottleneck/ryzen-5-9600x-rtx-5070/">Ryzen 5 9600X</a></td><td>AM5</td><td>CPU 6%</td><td>GPU-limited</td><td>GPU-limited</td><td>153</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/core-i5-13600k-rtx-5070/">Core i5-13600K</a></td><td>LGA 1700</td><td>CPU 8%</td><td>GPU-limited</td><td>GPU-limited</td><td>149</td><td>700W</td></tr>
<tr><td><a href="/hardware/ryzen-7-5800x3d/">Ryzen 7 5800X3D</a></td><td>AM4</td><td>CPU 8%</td><td>GPU-limited</td><td>GPU-limited</td><td>149</td><td>650W</td></tr>
<tr><td><a href="/hardware/ryzen-5-7600x/">Ryzen 5 7600X</a></td><td>AM5</td><td>CPU 10%</td><td>GPU-limited</td><td>GPU-limited</td><td>147</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/core-ultra-5-245k-rtx-5070/">Core Ultra 5 245K</a></td><td>LGA 1851</td><td>CPU 11%</td><td>GPU-limited</td><td>GPU-limited</td><td>145</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/ryzen-5-7600-rtx-5070/">Ryzen 5 7600</a></td><td>AM5</td><td>CPU 12%</td><td>GPU-limited</td><td>GPU-limited</td><td>142</td><td>650W</td></tr>
<tr><td><a href="/hardware/core-i7-12700k/">Core i7-12700K</a></td><td>LGA 1700</td><td>CPU 14%</td><td>GPU-limited</td><td>GPU-limited</td><td>140</td><td>700W</td></tr>
<tr><td><a href="/bottleneck/ryzen-7-5700x3d-rtx-5070/">Ryzen 7 5700X3D</a></td><td>AM4</td><td>CPU 14%</td><td>GPU-limited</td><td>GPU-limited</td><td>140</td><td>650W</td></tr>
<tr><td><a href="/hardware/ryzen-5-7500f/">Ryzen 5 7500F</a></td><td>AM5</td><td>CPU 15%</td><td>GPU-limited</td><td>GPU-limited</td><td>138</td><td>650W</td></tr>
<tr><td><a href="/hardware/core-i5-12600k/">Core i5-12600K</a></td><td>LGA 1700</td><td>CPU 19%</td><td>GPU-limited</td><td>GPU-limited</td><td>132</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/core-i5-13400f-rtx-5070/">Core i5-14400F</a></td><td>LGA 1700</td><td>CPU 22%</td><td>GPU-limited</td><td>GPU-limited</td><td>128</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/core-i5-13400f-rtx-5070/">Core i5-13400F</a></td><td>LGA 1700</td><td>CPU 23%</td><td>GPU-limited</td><td>GPU-limited</td><td>125</td><td>650W</td></tr>
<tr><td><a href="/hardware/ryzen-7-5800x/">Ryzen 7 5800X</a></td><td>AM4</td><td>CPU 25%</td><td>GPU-limited</td><td>GPU-limited</td><td>121</td><td>650W</td></tr>
<tr><td><a href="/hardware/core-i5-12400f/">Core i5-12400F</a></td><td>LGA 1700</td><td>CPU 28%</td><td>Balanced</td><td>GPU-limited</td><td>117</td><td>650W</td></tr>
<tr><td><a href="/hardware/ryzen-7-5700x/">Ryzen 7 5700X</a></td><td>AM4</td><td>CPU 28%</td><td>Balanced</td><td>GPU-limited</td><td>117</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/ryzen-5-5600x-rtx-5070/">Ryzen 5 5600X</a></td><td>AM4</td><td>CPU 29%</td><td>Balanced</td><td>GPU-limited</td><td>115</td><td>650W</td></tr>
<tr><td><a href="/bottleneck/ryzen-5-5600-rtx-5070/">Ryzen 5 5600</a></td><td>AM4</td><td>CPU 32%</td><td>Balanced</td><td>GPU-limited</td><td>111</td><td>650W</td></tr>
<tr><td><a href="/hardware/core-i3-12100f/">Core i3-12100F</a></td><td>LGA 1700</td><td>CPU 37%</td><td>CPU 8%</td><td>GPU-limited</td><td>102</td><td>650W</td></tr>
<tr><td><a href="/hardware/ryzen-5-3600/">Ryzen 5 3600</a></td><td>AM4</td><td>CPU 45%</td><td>CPU 19%</td><td>GPU-limited</td><td>89</td><td>650W</td></tr>
</tbody>
</table>
<p>Linked names open the full page for that pairing (or the processor's own page). With the fastest CPUs, the RTX 5070 itself tops out at about 163 FPS at 1080p, 111 at 1440p and 68 at 4K in our estimate. PSU sizes use our PSU Calculator's formula: the CPU at its maximum turbo or boost power, the RTX 5070's 250W, 2 RAM sticks, 2 drives, 3 fans, 30% headroom rounded up to 50W, and never below NVIDIA's 650W.</p>

<h2>Our picks by platform</h2>
<ul>
<li><strong>AMD AM5 (new build):</strong> <a href="/bottleneck/ryzen-5-7600-rtx-5070/">Ryzen 5 7600</a> is the slowest AM5 chip that is close enough at 1080p (12%, mild) (650W PSU); the <a href="/bottleneck/ryzen-7-7700x-rtx-5070/">Ryzen 7 7700X</a> removes the gap completely (650W PSU).</li>
<li><strong>Intel:</strong> <a href="/hardware/core-i7-12700k/">Core i7-12700K</a> is the slowest Intel chip that is close enough at 1080p (14%, mild) (700W PSU); the <a href="/hardware/core-ultra-7-265k/">Core Ultra 7 265K</a> removes the gap completely (800W PSU).</li>
<li><strong>Staying on AM4:</strong> <a href="/bottleneck/ryzen-7-5700x3d-rtx-5070/">Ryzen 7 5700X3D</a> is the slowest AM4 chip that is close enough at 1080p (14%, mild) (650W PSU), and no AM4 chip fully removes it, so on AM4 the RTX 5070 makes most sense for 1440p.</li>
</ul>

<h2>Older CPUs: is an RTX 5070 still worth it?</h2>
<p>With the <a href="/bottleneck/ryzen-5-5600-rtx-5070/">Ryzen 5 5600</a> or the <a href="/hardware/core-i5-12400f/">Core i5-12400F</a>, the RTX 5070 is balanced at 1440p but 32% and 28% CPU-limited at 1080p. If you game at 1440p, the card is a sensible upgrade on its own and the CPU can follow later. If you mainly play at 1080p, a card such as the <a href="/hardware/rtx-5060-ti/">RTX 5060 Ti 16GB</a> is a closer match for those CPUs.</p>
<p>On AM4 the drop-in upgrade is a 3D V-Cache chip: the <a href="/bottleneck/ryzen-7-5700x3d-rtx-5070/">Ryzen 7 5700X3D</a> brings the 1080p gap down to 14% and the Ryzen 7 5800X3D to 8%. Our <a href="/best-am4-cpu-for-gaming/">best AM4 CPU guide</a> covers that upgrade in detail.</p>
<p>The RTX 5070's 12GB of VRAM matches what our <a href="/how-much-vram-do-i-need/">VRAM guide</a> treats as comfortable for 1440p; at 4K with high textures, 16GB is the more comfortable figure.</p>

[cs_faq]
[cs_faq_item q="What CPU do I need for an RTX 5070?"]In our estimates, the Ryzen 7 7700X and faster keep the RTX 5070 fully busy at 1080p, and 10 more CPUs (from the Ryzen 7 5700X3D up) are within 15%. At 1440p and 4K the RTX 5070 is the limit with any CPU from the Ryzen 5 5600 and Core i5-12400F up.[/cs_faq_item]
[cs_faq_item q="Will a Ryzen 5 5600 bottleneck an RTX 5070?"]At 1080p, yes, by about 32% in our estimate. At 1440p the pair is balanced, and at 4K the RTX 5070 is the limit.[/cs_faq_item]
[cs_faq_item q="What power supply does an RTX 5070 need?"]NVIDIA lists 650W. With our PSU Calculator's formula, most CPUs in our list stay at 650W; the exceptions are: Core i7-12700K, Core i5-13600K and Core i5-14600K 700W; Ryzen 9 7900X and Ryzen 9 9950X3D 750W; Core Ultra 7 265K, Core i7-13700K, Core Ultra 9 285K, Core i7-14700K, Core i9-13900K and Core i9-14900K 800W, because of their higher turbo power.[/cs_faq_item]
[/cs_faq]

<p><em>All figures are specs-based estimates from ComputingSkill's engine (typical AAA game, high settings), not measured benchmarks. Real results vary by game, settings, drivers and memory speed.</em></p>
HTML
);
