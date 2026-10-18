<?php
/**
 * Draft post. Numbers come from cs_calculate_bottleneck() and the PSU
 * Calculator formula with data/hardware-data.php as of theme 1.0.36.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return array(
	'slug'     => 'best-cpu-for-rtx-5070',
	'title'    => 'Best CPU for RTX 5070: Which Processor Won’t Hold It Back?',
	'category' => 'Buying Guides',
	'excerpt'  => 'We paired the GeForce RTX 5070 with 19 CPUs in our bottleneck engine. Here is which processors keep it busy at 1080p and 1440p, and what power supply each build needs.',
	'content'  => <<<'HTML'
<p>NVIDIA's GeForce RTX 5070 is a 12GB GDDR7, 250W card aimed at 1440p, and NVIDIA recommends a 650W power supply for it (<a href="https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5070-family/" rel="noopener" target="_blank">NVIDIA specifications</a>). A card in this class can easily outrun an older or budget processor, especially at 1080p. So which CPU is enough?</p>
<p>We ran the RTX 5070 against every CPU in our database with the engine behind our <a href="/pc-bottleneck-calculator/">PC Bottleneck Calculator</a>. These are specs-based estimates, not benchmarks.</p>

<h2>Quick answer</h2>
<ul>
<li><strong>Balanced at 1080p:</strong> Ryzen 7 7700X, Ryzen 7 9700X, Core i7-13700K, Core i7-14700K, Core i9-13900K and Core Ultra 9 285K.</li>
<li><strong>Close at 1080p (under 15%):</strong> Ryzen 5 7600 (12%), Core Ultra 5 245K (11%), Ryzen 5 7600X (10%), Core i5-13600K (8%), Core i5-14600K and Ryzen 9 7900X (6%).</li>
<li><strong>Lowest-power close match:</strong> the Ryzen 5 7600 is within 12% at 1080p and has the lowest boost power of that group (88W), so the build stays at NVIDIA's 650W PSU figure.</li>
<li><strong>At 1440p and 4K</strong> the RTX 5070 is the limit with any of these CPUs from the Ryzen 5 7600 up.</li>
</ul>

<h2>RTX 5070 with 19 processors</h2>
<table>
<thead><tr><th>Processor</th><th>Socket</th><th>1080p</th><th>1440p</th><th>4K</th><th>PSU</th></tr></thead>
<tbody>
<tr><td>Intel Core i3-12100F</td><td>LGA 1700</td><td>CPU 37%</td><td>CPU 8%</td><td>GPU 33%</td><td>650W</td></tr>
<tr><td>AMD Ryzen 5 5600</td><td>AM4</td><td>CPU 32%</td><td>Balanced</td><td>GPU 38%</td><td>650W</td></tr>
<tr><td>AMD Ryzen 5 5600X</td><td>AM4</td><td>CPU 29%</td><td>Balanced</td><td>GPU 41%</td><td>650W</td></tr>
<tr><td>AMD Ryzen 7 5700X</td><td>AM4</td><td>CPU 28%</td><td>Balanced</td><td>GPU 42%</td><td>650W</td></tr>
<tr><td>Intel Core i5-12400F</td><td>LGA 1700</td><td>CPU 28%</td><td>Balanced</td><td>GPU 42%</td><td>650W</td></tr>
<tr><td>Intel Core i5-13400F</td><td>LGA 1700</td><td>CPU 23%</td><td>GPU 12%</td><td>GPU 46%</td><td>650W</td></tr>
<tr><td>AMD Ryzen 5 7600</td><td>AM5</td><td>CPU 12%</td><td>GPU 22%</td><td>GPU 52%</td><td>650W</td></tr>
<tr><td>Intel Core Ultra 5 245K</td><td>LGA 1851</td><td>CPU 11%</td><td>GPU 24%</td><td>GPU 53%</td><td>650W</td></tr>
<tr><td>AMD Ryzen 5 7600X</td><td>AM5</td><td>CPU 10%</td><td>GPU 25%</td><td>GPU 53%</td><td>650W</td></tr>
<tr><td>Intel Core i5-13600K</td><td>LGA 1700</td><td>CPU 8%</td><td>GPU 26%</td><td>GPU 54%</td><td>700W</td></tr>
<tr><td>AMD Ryzen 9 7900X</td><td>AM5</td><td>CPU 6%</td><td>GPU 28%</td><td>GPU 55%</td><td>750W</td></tr>
<tr><td>Intel Core i5-14600K</td><td>LGA 1700</td><td>CPU 6%</td><td>GPU 28%</td><td>GPU 55%</td><td>700W</td></tr>
<tr><td>AMD Ryzen 7 7700X</td><td>AM5</td><td>Balanced</td><td>GPU 29%</td><td>GPU 56%</td><td>650W</td></tr>
<tr><td>Intel Core i7-13700K</td><td>LGA 1700</td><td>Balanced</td><td>GPU 31%</td><td>GPU 57%</td><td>800W</td></tr>
<tr><td>AMD Ryzen 7 9700X</td><td>AM5</td><td>Balanced</td><td>GPU 31%</td><td>GPU 57%</td><td>650W</td></tr>
<tr><td>Intel Core Ultra 9 285K</td><td>LGA 1851</td><td>Balanced</td><td>GPU 32%</td><td>GPU 58%</td><td>800W</td></tr>
<tr><td>Intel Core i9-13900K</td><td>LGA 1700</td><td>Balanced</td><td>GPU 33%</td><td>GPU 59%</td><td>800W</td></tr>
<tr><td>Intel Core i7-14700K</td><td>LGA 1700</td><td>Balanced</td><td>GPU 33%</td><td>GPU 59%</td><td>800W</td></tr>
<tr><td>AMD Ryzen 7 9800X3D</td><td>AM5</td><td>GPU 20%</td><td>GPU 45%</td><td>GPU 60%</td><td>650W</td></tr>
</tbody>
</table>
<p>PSU figures use our <a href="/psu-calculator/">PSU Calculator</a>'s formula: the CPU at its maximum turbo or boost power, the RTX 5070's 250W, 2 RAM sticks, 2 drives, 3 fans, 30% headroom rounded up to 50W, and never below NVIDIA's 650W.</p>

<h2>Why the Ryzen 7 9800X3D shows "GPU 20%" at 1080p</h2>
<p>The Ryzen 7 9800X3D is the fastest gaming CPU in our database, fast enough that even at 1080p the RTX 5070 becomes the limit. That is not a bad pairing: it simply means the CPU has headroom left for a faster graphics card later. If you plan to keep the CPU through more than one GPU upgrade, that headroom is worth having.</p>

<h2>Older CPUs: is an RTX 5070 still worth it?</h2>
<p>With AM4 chips like the <a href="/bottleneck/ryzen-5-5600-rtx-5070/">Ryzen 5 5600</a> or Intel's Core i5-12400F, the RTX 5070 is balanced at 1440p but 28% to 32% CPU-limited at 1080p. If you game at 1440p, the card is a sensible upgrade on its own and the CPU can follow later. If you mainly play at 1080p, a cheaper card such as the RTX 5060 Ti 16GB is a closer match for those CPUs.</p>
<p>The Core i3-12100F is the one CPU in our list still CPU-limited at 1440p with the RTX 5070 (about 8%), and its 4 cores make it the weakest choice here.</p>

<h2>Our picks</h2>
<ul>
<li><strong>Balanced and efficient:</strong> <a href="/bottleneck/ryzen-7-7700x-rtx-5070/">Ryzen 7 7700X</a> or <a href="/bottleneck/ryzen-7-7700x-rtx-5070/">Ryzen 7 9700X</a>, both balanced at 1080p and fine on a 650W PSU.</li>
<li><strong>Intel:</strong> <a href="/bottleneck/core-i5-14600k-rtx-5070/">Core i5-14600K</a> (6% at 1080p, 700W PSU) or the <a href="/bottleneck/core-ultra-5-245k-rtx-5070/">Core Ultra 5 245K</a> (11%, 650W).</li>
<li><strong>Staying on AM4:</strong> the Ryzen 5 5600, 5600X and 5700X are all balanced with the RTX 5070 at 1440p but 28% to 32% CPU-limited at 1080p, so on AM4 the card makes most sense for 1440p gaming.</li>
<li><strong>Budget AM5:</strong> <a href="/bottleneck/ryzen-5-7600-rtx-5070/">Ryzen 5 7600</a>, 12% at 1080p and the lowest power of the group.</li>
</ul>
<p>The RTX 5070's 12GB of VRAM matches what our <a href="/how-much-vram-do-i-need/">VRAM guide</a> treats as comfortable for 1440p; at 4K with high textures, 16GB is the more comfortable figure.</p>

[cs_faq]
[cs_faq_item q="What CPU do I need for an RTX 5070?"]In our estimates, CPUs from the Ryzen 5 7600 and Core Ultra 5 245K upward keep the RTX 5070 within about 12% at 1080p and leave it as the limit at 1440p and 4K. The Ryzen 7 7700X, Ryzen 7 9700X and Core i7-13700K and above are balanced at 1080p.[/cs_faq_item]
[cs_faq_item q="Will a Ryzen 5 5600 bottleneck an RTX 5070?"]At 1080p, yes, by about 32% in our estimate. At 1440p the pair is balanced, and at 4K the RTX 5070 is the limit.[/cs_faq_item]
[cs_faq_item q="What power supply does an RTX 5070 need?"]NVIDIA recommends 650W. With our PSU Calculator's formula, most CPUs in our list stay at 650W; the Core i5-13600K and i5-14600K come to 700W, the Ryzen 9 7900X 750W, and the Core i7-13700K, i7-14700K, i9-13900K and Ultra 9 285K 800W because of their higher turbo power.[/cs_faq_item]
[/cs_faq]

<p><em>All figures are specs-based estimates from ComputingSkill's engine, not measured benchmarks. Real results vary by game, settings, drivers and memory speed.</em></p>
HTML
);
