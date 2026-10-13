/**
 * ComputingSkill calculator engine.
 *
 * A small declarative framework: each calculator is a definition object
 * (fields + a compute function) registered in CS_DEFS. A single generic
 * runner builds the form, validates input (required / non-numeric / out
 * of range), and renders the result. This keeps every calculator's
 * invalid/empty/extreme-input handling consistent instead of hand-rolled
 * per tool.
 *
 * Hardware specs come from window.csHardwareData (localized from the
 * verified dataset in data/hardware-data.php by inc/setup.php) — never
 * hard-coded here.
 */
(function () {
	'use strict';

	var HW = window.csHardwareData || { gpu: {}, cpu: {} };
	// Publisher-listed game requirements (data/games-data.php via
	// inc/setup.php) for the FPS calculator's optional game picker.
	var GAMES = window.csGamesData || {};
	function gameOptions() {
		var opts = [ { value: '', label: 'Not a specific game' } ];
		Object.keys( GAMES ).forEach( function ( key ) {
			opts.push( { value: key, label: GAMES[ key ].name } );
		} );
		return opts;
	}

	/* ----------------------------------------------------------------
	 * Small DOM + formatting helpers
	 * ---------------------------------------------------------------- */

	function escapeHtml( str ) {
		return String( str ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function fmt( num, decimals ) {
		if ( ! isFinite( num ) ) {
			return '—';
		}
		var d = decimals === undefined ? 1 : decimals;
		// toLocaleString() alone stops at 3 decimals, so a 4-decimal result
		// lost its last digit (and tiny values showed as 0).
		return Number( num.toFixed( d ) ).toLocaleString( undefined, { maximumFractionDigits: d } );
	}

	function clamp( n, min, max ) {
		return Math.max( min, Math.min( max, n ) );
	}

	// The CPU/GPU "score" fields are a relative ranking, not a fixed 0-100
	// scale — a newer flagship part can score above 100 once it's confirmed
	// faster than the previous top part (see data/hardware-data.php's
	// rtx-5090 comment for why this stopped being safe to hard-code as
	// "/ 100" during the Sep 2026 audit). Compute the real ceiling from
	// whichever hardware map is being displayed so the label never lies.
	function hwScoreMax( map ) {
		var max = 0;
		Object.keys( map || {} ).forEach( function ( k ) {
			if ( map[ k ] && map[ k ].score > max ) {
				max = map[ k ].score;
			}
		} );
		return max || 100;
	}

	function secondsToHuman( totalSeconds ) {
		if ( ! isFinite( totalSeconds ) || totalSeconds < 0 ) {
			return '—';
		}
		if ( totalSeconds < 1 ) {
			return '< 1 second';
		}
		// Round once up front: rounding only the seconds part turned 119.7s
		// into "1m 60s".
		totalSeconds = Math.round( totalSeconds );
		var days = Math.floor( totalSeconds / 86400 );
		var hours = Math.floor( ( totalSeconds % 86400 ) / 3600 );
		var minutes = Math.floor( ( totalSeconds % 3600 ) / 60 );
		var seconds = Math.round( totalSeconds % 60 );
		var parts = [];
		if ( days ) parts.push( days + 'd' );
		if ( hours ) parts.push( hours + 'h' );
		if ( minutes ) parts.push( minutes + 'm' );
		if ( seconds || ! parts.length ) parts.push( seconds + 's' );
		return parts.join( ' ' );
	}

	function gcd( a, b ) {
		a = Math.abs( Math.round( a ) );
		b = Math.abs( Math.round( b ) );
		while ( b ) {
			var t = b;
			b = a % b;
			a = t;
		}
		return a || 1;
	}

	function hardwareOptions( source ) {
		var data = HW[ source ] || {};
		var options = [];
		Object.keys( data ).forEach( function ( key ) {
			options.push( { value: key, label: data[ key ].name } );
		} );
		return options;
	}

	/* ----------------------------------------------------------------
	 * Field icons — small original line-icon SVGs (currentColor), shown
	 * on fields that opt in with an `icon` key in their definition. Purely
	 * decorative UI iconography, not a depiction of any real product.
	 * ---------------------------------------------------------------- */

	var FIELD_ICONS = {
		cpu: '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="14" y="14" width="20" height="20" rx="2" stroke="currentColor" stroke-width="2.4"/><rect x="19" y="19" width="10" height="10" rx="1" stroke="currentColor" stroke-width="2.2"/><g stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M20 14v-5M28 14v-5M20 39v-5M28 39v-5"/><path d="M14 20H9M14 28H9M39 20h-5M39 28h-5"/></g></svg>',
		gpu: '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="7" y="15" width="34" height="16" rx="2.5" stroke="currentColor" stroke-width="2.4"/><circle cx="15" cy="23" r="4" stroke="currentColor" stroke-width="2.2"/><circle cx="27" cy="23" r="4" stroke="currentColor" stroke-width="2.2"/><path d="M35 15v-3a2 2 0 0 1 2-2h2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M11 31v4M17 31v4M23 31v4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>',
		resolution: '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="10" width="36" height="23" rx="2.5" stroke="currentColor" stroke-width="2.4"/><path d="M18 40h12M24 33v7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>',
		ram: '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="12" width="32" height="20" rx="2" stroke="currentColor" stroke-width="2.4"/><path d="M15 32v5M22 32v5M29 32v5M36 32v5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M14 19h6M14 24h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>',
		storage: '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="9" width="32" height="30" rx="3" stroke="currentColor" stroke-width="2.4"/><path d="M8 26h32" stroke="currentColor" stroke-width="2.4"/><circle cx="15" cy="32.5" r="2" fill="currentColor"/><path d="M22 32.5h11" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>',
		usage: '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24 8l14 7v10c0 9-6 15-14 17-8-2-14-8-14-17V15l14-7z" stroke="currentColor" stroke-width="2.3" stroke-linejoin="round"/><path d="M18.5 24l4 4 8-8.5" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></svg>'
	};

	/* ----------------------------------------------------------------
	 * Field builder
	 * ---------------------------------------------------------------- */

	function buildField( field ) {
		var wrapper = document.createElement( 'div' );
		wrapper.className = 'cs-field cs-field-' + field.type;

		var iconKey = field.icon && FIELD_ICONS[ field.icon ] ? field.icon : null;
		if ( iconKey ) {
			wrapper.classList.add( 'cs-field-has-icon' );
		}

		var main = document.createElement( 'div' );
		main.className = 'cs-field-main';

		var label = document.createElement( 'label' );
		var inputId = 'cs-f-' + field.id + '-' + Math.random().toString( 36 ).slice( 2, 7 );
		label.setAttribute( 'for', inputId );
		label.textContent = field.label;
		main.appendChild( label );

		var input;

		if ( field.type === 'select' || field.type === 'select-hardware' ) {
			input = document.createElement( 'select' );
			var opts = field.type === 'select-hardware' ? hardwareOptions( field.source ) : field.options;
			opts.forEach( function ( opt ) {
				var o = document.createElement( 'option' );
				o.value = opt.value;
				o.textContent = opt.label;
				input.appendChild( o );
			} );
			if ( field.default !== undefined ) {
				input.value = field.default;
			}
		} else if ( field.type === 'checkbox' ) {
			input = document.createElement( 'input' );
			input.type = 'checkbox';
			input.checked = !! field.default;
			wrapper.classList.add( 'cs-field-inline' );
		} else {
			input = document.createElement( 'input' );
			input.type = 'number';
			if ( field.min !== undefined ) input.min = field.min;
			if ( field.max !== undefined ) input.max = field.max;
			if ( field.step !== undefined ) input.step = field.step;
			if ( field.default !== undefined ) input.value = field.default;
			if ( field.placeholder ) input.placeholder = field.placeholder;
		}

		input.id = inputId;
		main.appendChild( input );

		if ( field.help ) {
			var help = document.createElement( 'p' );
			help.className = 'cs-field-help';
			help.textContent = field.help;
			main.appendChild( help );
		}

		wrapper.appendChild( main );

		if ( iconKey ) {
			var iconBox = document.createElement( 'div' );
			iconBox.className = 'cs-field-icon';
			iconBox.setAttribute( 'aria-hidden', 'true' );
			iconBox.innerHTML = FIELD_ICONS[ iconKey ];
			wrapper.appendChild( iconBox );
		}

		return { wrapper: wrapper, input: input };
	}

	/**
	 * Reads + validates one field's current value.
	 * Returns { value, warning } on success.
	 * Throws { msg } on a hard error (empty required field / non-numeric).
	 */
	function readField( input, field ) {
		if ( field.type === 'select' || field.type === 'select-hardware' ) {
			return { value: input.value };
		}
		if ( field.type === 'checkbox' ) {
			return { value: input.checked };
		}

		var raw = input.value.trim();

		if ( raw === '' ) {
			if ( field.required === false ) {
				// `blankValue` lets a field report "left empty" instead of
				// silently falling back to its default (Refresh Rate uses this
				// so an empty form is an error, not a hidden 144 Hz).
				if ( field.blankValue !== undefined ) {
					return { value: field.blankValue };
				}
				return { value: field.default !== undefined ? field.default : 0 };
			}
			throw { msg: '"' + field.label + '" is required. Please enter a value.' };
		}

		var num = parseFloat( raw );
		if ( isNaN( num ) ) {
			throw { msg: '"' + field.label + '" must be a number.' };
		}

		var warning = null;
		if ( field.min !== undefined && num < field.min ) {
			warning = '"' + field.label + '" was below the realistic minimum, so ' + field.min + ' was used instead.';
			num = field.min;
		}
		if ( field.max !== undefined && num > field.max ) {
			warning = '"' + field.label + '" was above the realistic maximum, so ' + field.max + ' was used instead.';
			num = field.max;
		}

		return { value: num, warning: warning };
	}

	/* ----------------------------------------------------------------
	 * Shareable results (Phase 5 audit fix, Oct 2026)
	 *
	 * A definition with `share: true` writes its inputs into the page URL
	 * on every successful Calculate (history.replaceState, so no extra
	 * history entries), e.g. ?cpu=ryzen-5-5600&gpu=rtx-4060. On load those
	 * parameters pre-fill the form and the result is calculated at once, so
	 * a copied link reproduces exactly the same result. Fields still at
	 * their default are left out to keep links short; loading falls back to
	 * the same default, so the result is identical. Unknown or invalid
	 * values are ignored and removed from the address bar. Other query
	 * parameters (utm_*, etc.) are left alone. Only the first share-enabled
	 * calculator on a page owns the URL. The page's canonical URL stays the
	 * clean page address (see cs_canonical_without_share_params()).
	 * ---------------------------------------------------------------- */

	var shareOwner = null;

	function shareParams() {
		try {
			return new URLSearchParams( window.location.search );
		} catch ( e ) {
			return null;
		}
	}

	function writeShareUrl( params ) {
		if ( ! window.history || ! window.history.replaceState ) {
			return;
		}
		var qs = params.toString();
		var url = window.location.pathname + ( qs ? '?' + qs : '' ) + window.location.hash;
		try {
			window.history.replaceState( window.history.state, '', url );
		} catch ( e ) {}
	}

	// Validates one URL value against its field. Returns the value to set
	// on the input, or null when it is not a valid choice for that field.
	function shareValueFor( field, input, raw ) {
		if ( field.type === 'select' || field.type === 'select-hardware' ) {
			for ( var i = 0; i < input.options.length; i++ ) {
				if ( input.options[ i ].value === raw ) {
					return raw;
				}
			}
			return null;
		}
		if ( field.type === 'checkbox' ) {
			return raw === '1' ? true : ( raw === '0' ? false : null );
		}
		if ( ! /^-?\d+(\.\d+)?$/.test( raw ) ) {
			return null;
		}
		var num = parseFloat( raw );
		if ( ( field.min !== undefined && num < field.min ) || ( field.max !== undefined && num > field.max ) ) {
			return null;
		}
		return raw;
	}

	// Pre-fills inputs from the URL. Returns true when at least one valid
	// value was applied and none was invalid (the caller then
	// auto-calculates). With a bad value the form is only pre-filled, so a
	// broken link never silently shows a result for a part nobody picked.
	function applyShareParams( def, inputs ) {
		var params = shareParams();
		if ( ! params ) {
			return false;
		}
		var applied = false, dirty = false;
		def.fields.forEach( function ( field ) {
			if ( ! params.has( field.id ) ) {
				return;
			}
			var input = inputs[ field.id ];
			var val = shareValueFor( field, input, params.get( field.id ) );
			if ( val === null ) {
				params.delete( field.id );
				dirty = true;
				return;
			}
			if ( field.type === 'checkbox' ) {
				input.checked = val;
			} else {
				input.value = val;
			}
			applied = true;
		} );
		if ( dirty ) {
			writeShareUrl( params );
		}
		return applied && ! dirty;
	}

	function updateShareUrl( def, values ) {
		var params = shareParams();
		if ( ! params ) {
			return;
		}
		def.fields.forEach( function ( field ) {
			var v = values[ field.id ];
			var isDefault = field.default !== undefined && ( field.type === 'checkbox' ? !! field.default === !! v : String( field.default ) === String( v ) );
			if ( isDefault || v === '' || v === undefined ) {
				params.delete( field.id );
			} else {
				params.set( field.id, field.type === 'checkbox' ? ( v ? '1' : '0' ) : String( v ) );
			}
		} );
		writeShareUrl( params );
	}

	function clearShareUrl( def ) {
		var params = shareParams();
		if ( ! params ) {
			return;
		}
		def.fields.forEach( function ( field ) { params.delete( field.id ); } );
		writeShareUrl( params );
	}

	// Link to the static /bottleneck/{cpu}-{gpu}/ page when that pair has
	// one (published pages only, handed over by inc/setup.php).
	function comboPageLink( cpuSlug, gpuSlug, hw ) {
		var pages = window.csComboPages || {};
		var url = pages[ cpuSlug + '|' + gpuSlug ];
		if ( ! url || ! hw.cpu[ cpuSlug ] || ! hw.gpu[ gpuSlug ] ) {
			return '';
		}
		return '<p class="cs-share-combo">Full breakdown for this pair: <a href="' + escapeHtml( url ) + '">' + escapeHtml( hw.cpu[ cpuSlug ].name + ' + ' + hw.gpu[ gpuSlug ].name + ' bottleneck page' ) + '</a></p>';
	}

	function shareBarHtml() {
		return '<div class="cs-share"><button type="button" class="cs-share-copy" data-role="copy-link">Copy link to this result</button> <span class="cs-share-status" role="status" aria-live="polite"></span></div>';
	}

	function copyText( text, done ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then( function () { done( true ); }, function () { done( fallbackCopy( text ) ); } );
			return;
		}
		done( fallbackCopy( text ) );
	}

	function fallbackCopy( text ) {
		var ta = document.createElement( 'textarea' );
		ta.value = text;
		ta.setAttribute( 'readonly', '' );
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild( ta );
		ta.select();
		var ok = false;
		try {
			ok = document.execCommand( 'copy' );
		} catch ( e ) {}
		document.body.removeChild( ta );
		return ok;
	}

	/* ----------------------------------------------------------------
	 * Generic runner
	 * ---------------------------------------------------------------- */

	function initCalculator( container ) {
		var type = container.getAttribute( 'data-calc' );
		var def = CS_DEFS[ type ];
		if ( ! def ) {
			return;
		}

		var fieldsRoot = container.querySelector( '[data-role="fields"]' );
		var resultRoot = container.querySelector( '[data-role="result"]' );
		var inputs = {};

		def.fields.forEach( function ( field ) {
			var built = buildField( field );
			fieldsRoot.appendChild( built.wrapper );
			inputs[ field.id ] = built.input;
		} );

		// Mutually-exclusive field pairs (a field definition sets
		// `clears: '<other field id>'`, e.g. the Refresh Rate calculator's
		// Hz / ms fields): typing into one immediately blanks the other, so
		// a stale leftover value in the field the user isn't using can
		// never silently override what they just typed.
		def.fields.forEach( function ( field ) {
			if ( ! field.clears ) {
				return;
			}
			var input = inputs[ field.id ];
			var otherInput = inputs[ field.clears ];
			if ( ! input || ! otherInput ) {
				return;
			}
			input.addEventListener( 'input', function () {
				if ( input.value.trim() !== '' ) {
					otherInput.value = '';
				}
			} );
		} );

		var shares = !! def.share && ! shareOwner;
		if ( shares ) {
			shareOwner = container;
		}

		function calculate() {
			var values = {};
			var warnings = [];

			try {
				def.fields.forEach( function ( field ) {
					var result = readField( inputs[ field.id ], field );
					values[ field.id ] = result.value;
					if ( result.warning ) {
						warnings.push( result.warning );
					}
				} );
			} catch ( e ) {
				resultRoot.classList.add( 'has-error' );
				resultRoot.innerHTML = '<p class="cs-error" role="alert">' + escapeHtml( e.msg ) + '</p>';
				return;
			}

			resultRoot.classList.remove( 'has-error' );

			var outputHtml;
			try {
				outputHtml = def.compute( values, HW );
			} catch ( e ) {
				resultRoot.classList.add( 'has-error' );
				// A compute() may throw { msg } for a known input problem
				// (e.g. both Refresh Rate fields left empty); show that exact
				// message instead of the generic fallback.
				var msg = e && e.msg ? e.msg : 'Something went wrong calculating that. Please check your inputs and try again.';
				resultRoot.innerHTML = '<p class="cs-error" role="alert">' + escapeHtml( msg ) + '</p>';
				return;
			}

			var html = '';
			if ( warnings.length ) {
				html += '<div class="cs-warnings">' + warnings.map( function ( w ) {
					return '<p class="cs-warning">⚠ ' + escapeHtml( w ) + '</p>';
				} ).join( '' ) + '</div>';
			}
			html += outputHtml;
			if ( def.comboLink ) {
				html += comboPageLink( values.cpu, values.gpu, HW );
			}
			if ( shares ) {
				updateShareUrl( def, values );
				html += shareBarHtml();
			}
			resultRoot.innerHTML = html;
			resultRoot.setAttribute( 'tabindex', '-1' );
			resultRoot.focus( { preventScroll: true } );
		}

		var submitBtn = container.querySelector( '[data-role="submit"]' );
		var resetBtn = container.querySelector( '[data-role="reset"]' );

		if ( submitBtn ) {
			submitBtn.addEventListener( 'click', calculate );
		}

		container.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' && e.target.tagName !== 'BUTTON' ) {
				e.preventDefault();
				calculate();
			}
		} );

		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', function () {
				def.fields.forEach( function ( field ) {
					var input = inputs[ field.id ];
					if ( field.type === 'checkbox' ) {
						input.checked = !! field.default;
					} else if ( field.default !== undefined ) {
						input.value = field.default;
					} else {
						input.value = '';
					}
				} );
				resultRoot.classList.remove( 'has-error' );
				resultRoot.innerHTML = '';
				if ( shares ) {
					clearShareUrl( def );
				}
			} );
		}

		if ( shares ) {
			resultRoot.addEventListener( 'click', function ( e ) {
				var btn = e.target.closest ? e.target.closest( '[data-role="copy-link"]' ) : null;
				if ( ! btn ) {
					return;
				}
				var status = resultRoot.querySelector( '.cs-share-status' );
				var url = window.location.href;
				copyText( url, function ( ok ) {
					if ( ok ) {
						status.textContent = 'Link copied.';
					} else {
						window.prompt( 'Copy this link:', url );
					}
				} );
			} );
			if ( applyShareParams( def, inputs ) ) {
				calculate();
			}
		}
	}

	/* ----------------------------------------------------------------
	 * Shared result-rendering helpers
	 * ---------------------------------------------------------------- */

	function resultBlock( headline, rows, disclaimer ) {
		var html = '<div class="cs-result-block">';
		html += '<p class="cs-result-headline">' + headline + '</p>';
		if ( rows && rows.length ) {
			html += '<table class="cs-result-table"><tbody>';
			rows.forEach( function ( r ) {
				html += '<tr><th>' + escapeHtml( r[ 0 ] ) + '</th><td>' + r[ 1 ] + '</td></tr>';
			} );
			html += '</tbody></table>';
		}
		if ( disclaimer !== false ) {
			html += '<p class="cs-disclaimer"><strong>Estimate only:</strong> ' + ( disclaimer || 'this is a simplified model to guide your decision, not a lab-measured result. Real-world numbers vary by brand, drivers, cooling and workload.' ) + '</p>';
		}
		html += '</div>';
		return html;
	}

	/* ----------------------------------------------------------------
	 * Calculator definitions
	 * ---------------------------------------------------------------- */

	var RES_LABELS = { '1080p': '1080p (Full HD)', '1440p': '1440p (QHD)', '4k': '4K (UHD)' };

	// Phase 2 audit fix (Oct 2026): per-resolution engine. Must stay identical
	// to cs_calculate_bottleneck() in inc/bottleneck.php — see that file's
	// header for the full method. In short: the CPU's gaming ceiling
	// (score * BN_CPU_PARITY) barely moves with resolution, while the GPU's
	// throughput shrinks with pixel count (score * BN_GPU_RES_FACTOR[res]).
	// Comparing the two separately at each resolution lets a pair be
	// CPU-bound at 1080p and GPU-bound at 4K. The old model used one score
	// gap for all three resolutions, so the direction could never change.
	var BN_CPU_PARITY = 0.85;
	var BN_GPU_RES_FACTOR = { '1080p': 1.0, '1440p': 0.68, '4k': 0.42 };

	function bottleneckEstimate( cpuScore, gpuScore ) {
		var cpuEff = cpuScore * BN_CPU_PARITY;
		var out = {};
		Object.keys( BN_GPU_RES_FACTOR ).forEach( function ( res ) {
			var gpuEff = gpuScore * BN_GPU_RES_FACTOR[ res ];
			var stronger = Math.max( cpuEff, gpuEff );
			// Rounded to 4 decimals first, same as the PHP mirror, so both agree
			// on values that land exactly on .5 after float math.
			var share = stronger > 0 ? Number( ( Math.abs( gpuEff - cpuEff ) / stronger * 100 ).toFixed( 4 ) ) : 0;
			var percent = Math.min( 60, Math.floor( share + 0.5 ) );
			var component = gpuEff > cpuEff ? 'cpu' : 'gpu'; // GPU has spare capacity => CPU is the limit.
			if ( percent < 6 ) {
				component = 'balanced';
			}
			out[ res ] = { percent: percent, component: component };
		} );
		return out;
	}

	function bnLabel( r ) {
		if ( r.component === 'balanced' ) {
			return r.percent + '% (balanced)';
		}
		return r.percent + '% (' + ( r.component === 'cpu' ? 'CPU-bound' : 'GPU-bound' ) + ')';
	}

	// Plain-language note when the likely limiting part changes between
	// resolutions; empty string when it doesn't.
	function bnShiftNote( all ) {
		var seen = {};
		Object.keys( all ).forEach( function ( res ) { seen[ all[ res ].component ] = true; } );
		if ( Object.keys( seen ).length < 2 ) {
			return '';
		}
		var parts = Object.keys( all ).map( function ( res ) {
			var r = all[ res ];
			return ( r.component === 'balanced' ? 'balanced' : ( r.component === 'cpu' ? 'CPU-bound' : 'GPU-bound' ) ) + ' at ' + ( res === '4k' ? '4K' : res );
		} );
		return 'The limiting part shifts with resolution: ' + parts[ 0 ] + ', ' + parts[ 1 ] + ' and ' + parts[ 2 ] + '. Higher resolutions give the GPU more pixels to draw, so load moves onto the graphics card.';
	}

	// Phase 8 (Oct 2026): visual for the bottleneck result, a ring gauge for
	// the chosen resolution plus one bar per resolution. Display only; it
	// reads the same bottleneckEstimate() output as the text and table.
	var BN_RES_SHORT = { '1080p': '1080p', '1440p': '1440p', '4k': '4K' };
	function bnWord( r ) {
		if ( r.component === 'balanced' ) {
			return 'balanced';
		}
		return r.component === 'cpu' ? 'CPU-bound' : 'GPU-bound';
	}
	function bnVisual( all, res ) {
		var r = all[ res ];
		var circ = 314.16; // 2 * PI * r(50)
		var offset = ( circ * ( 1 - Math.max( r.percent, 2 ) / 100 ) ).toFixed( 2 );
		var html = '<div class="cs-bn-visual">';
		html += '<div class="cs-gauge cs-lb-' + r.component + '"><svg viewBox="0 0 120 120" aria-hidden="true" focusable="false"><circle class="cs-gauge-track" cx="60" cy="60" r="50"/><circle class="cs-gauge-arc" cx="60" cy="60" r="50" stroke-dasharray="' + circ + '" stroke-dashoffset="' + offset + '"/></svg>';
		html += '<div class="cs-gauge-c"><strong>' + r.percent + '%</strong><small>' + bnWord( r ) + '</small><small>' + BN_RES_SHORT[ res ] + '</small></div></div>';
		html += '<div class="cs-lb" role="list">';
		Object.keys( all ).forEach( function ( k ) {
			var x = all[ k ];
			var width = Math.max( 4, Math.min( 100, Math.round( x.percent / 60 * 100 ) ) );
			html += '<div class="cs-lb-row cs-lb-' + x.component + ( k === res ? ' is-current' : '' ) + '" role="listitem"><span class="cs-lb-res">' + BN_RES_SHORT[ k ] + '</span><span class="cs-lb-track" aria-hidden="true"><span class="cs-lb-fill" style="width:' + width + '%"></span></span><span class="cs-lb-val">' + x.percent + '% ' + bnWord( x ) + '</span></div>';
		} );
		html += '</div></div>';
		return html;
	}

	var CS_DEFS = {};

	/* ---- 1. PC Bottleneck Calculator ---- */
	CS_DEFS.bottleneck = {
		share: true,
		comboLink: true,
		fields: [
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', label: 'Your CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'Your GPU', icon: 'gpu' },
			{ id: 'resolution', type: 'select', label: 'Resolution', icon: 'resolution', options: [
				{ value: '1080p', label: '1080p (Full HD)' }, { value: '1440p', label: '1440p (QHD)' }, { value: '4k', label: '4K (UHD)' }
			], default: '1080p' }
		],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ], gpu = hw.gpu[ v.gpu ];
			var all = bottleneckEstimate( cpu.score, gpu.score );
			var r = all[ v.resolution ];
			var verdict;
			if ( r.component === 'balanced' ) {
				verdict = 'These parts are well matched at ' + RES_LABELS[ v.resolution ] + '. Neither is a significant limiting factor.';
			} else if ( r.component === 'cpu' ) {
				verdict = 'The ' + cpu.name + ' is the more likely limiting factor at ' + RES_LABELS[ v.resolution ] + ' (~' + r.percent + '% estimated bottleneck).';
			} else {
				verdict = 'The ' + gpu.name + ' is the more likely limiting factor at ' + RES_LABELS[ v.resolution ] + ' (~' + r.percent + '% estimated bottleneck).';
			}
			var shift = bnShiftNote( all );
			if ( shift ) {
				verdict += ' ' + shift;
			}
			var rows = [
				[ 'Estimated bottleneck', r.percent + '%' ],
				[ 'Likely limiting part', r.component === 'balanced' ? 'Balanced' : ( r.component === 'cpu' ? cpu.name : gpu.name ) ],
				[ '1080p', bnLabel( all[ '1080p' ] ) ],
				[ '1440p', bnLabel( all[ '1440p' ] ) ],
				[ '4K', bnLabel( all[ '4k' ] ) ]
			];
			return bnVisual( all, v.resolution ) + resultBlock( verdict, rows, 'bottleneck estimates use relative specs-based performance scores, not per-game benchmarks.' );
		}
	};

	/* ---- 2. PSU Calculator ---- */
	/*
	 * Shared CPU picker for the PSU, PC Power Consumption and PC Cooling
	 * calculators (Sep 2026 audit). They used to take a hand-typed "CPU TDP"
	 * number, so an i9-13900K user naturally typed 125W — its base power —
	 * even though Intel's own spec page lists 253W Maximum Turbo Power, which
	 * is what the PSU and cooler actually have to handle. Picking the CPU
	 * from the verified dataset now pulls BOTH figures from
	 * data/hardware-data.php (tdp_w and max_power_w, each sourced there).
	 * "Other CPU" keeps a manual watts box for parts not in the list.
	 */
	function cpuPickerFields( wattsLabel, wattsHelp ) {
		var opts = hardwareOptions( 'cpu' ).slice();
		opts.push( { value: 'custom', label: 'Other CPU — enter its watts below' } );
		return [
			{ id: 'cpu', type: 'select', label: 'CPU', icon: 'cpu', default: HW.cpu && HW.cpu[ 'ryzen-5-7600' ] ? 'ryzen-5-7600' : ( opts[ 0 ] ? opts[ 0 ].value : 'custom' ), options: opts },
			{ id: 'cpuTdp', type: 'number', label: wattsLabel, default: 125, min: 15, max: 400, help: wattsHelp }
		];
	}
	function resolveCpuPower( v, hw ) {
		var c = v.cpu && v.cpu !== 'custom' && hw.cpu ? hw.cpu[ v.cpu ] : null;
		if ( ! c ) {
			return { name: 'Your CPU', tdp: v.cpuTdp, max: v.cpuTdp, custom: true };
		}
		return { name: c.name, tdp: c.tdp_w, max: c.max_power_w || c.tdp_w, custom: false };
	}

	CS_DEFS.psu = {
		share: true,
		fields: [
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU', icon: 'gpu' }
		].concat( cpuPickerFields( 'Other CPU: max turbo/boost power in watts (only used with "Other CPU")', 'Intel lists this as "Maximum Turbo Power"; for AMD Ryzen use the PPT figure (about TDP × 1.35).' ) ).concat( [
			{ id: 'ramSticks', type: 'number', label: 'Number of RAM sticks', default: 2, min: 1, max: 8 },
			{ id: 'storageDrives', type: 'number', label: 'Storage drives (SSD/HDD)', default: 2, min: 0, max: 8 },
			{ id: 'fans', type: 'number', label: 'Case/AIO fans', default: 3, min: 0, max: 12 },
			{ id: 'overclock', type: 'checkbox', label: 'I plan to overclock the CPU and/or GPU', default: false }
		] ),
		compute: function ( v, hw ) {
			var gpu = hw.gpu[ v.gpu ];
			var cpu = resolveCpuPower( v, hw );
			// CPU at its maximum turbo/boost power (not base TDP): a PSU has
			// to carry the peak the CPU is actually allowed to pull.
			var base = cpu.max + gpu.tdp_w + ( v.ramSticks * 5 ) + ( v.storageDrives * 6 ) + ( v.fans * 3 ) + 50; // 50W: motherboard + misc.
			if ( v.overclock ) {
				base *= 1.15;
			}
			var estimated = Math.ceil( ( base * 1.3 ) / 50 ) * 50; // 30% headroom over THIS build's parts, rounded to nearest 50W.
			// Never recommend less than the GPU vendor's own published PSU
			// guidance (data/hardware-data.php `recommended_psu_w`) — that
			// figure already bakes in headroom this component-tally can't
			// see (transient power-draw spikes well above rated TDP, and
			// staying in the PSU's efficient load range), so a bare parts
			// tally must never undercut it. Fixes a real conflict found in
			// audit: RTX 4070 build computed 450W here while the PSU page's
			// own FAQ (sourced from this same recommended_psu_w figure)
			// said 650W.
			var manufacturerRec = gpu.recommended_psu_w || 0;
			var recommended = Math.max( estimated, manufacturerRec );
			var rows = [
				[ 'Estimated system draw under load', fmt( base, 0 ) + 'W' ],
				[ 'Recommended PSU wattage (with headroom)', recommended + 'W' ],
				[ 'GPU (' + gpu.name + ')', gpu.tdp_w + 'W' ],
				[ 'GPU vendor’s own recommended PSU', manufacturerRec ? manufacturerRec + 'W' : '—' ],
				[ 'CPU (' + cpu.name + ', max turbo/boost power)', cpu.max + 'W' ]
			];
			return resultBlock( 'Get at least a <strong>' + recommended + 'W</strong> power supply from a reputable brand with 80+ Bronze certification or better.', rows );
		}
	};

	/* ---- 3. PC Power Consumption Calculator ---- */
	CS_DEFS['power-consumption'] = {
		fields: [
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU', icon: 'gpu' }
		].concat( cpuPickerFields( 'Other CPU: rated TDP in watts (only used with "Other CPU")', 'Average draw while gaming tracks the rated TDP, not the brief turbo peak.' ) ).concat( [
			{ id: 'hoursPerDay', type: 'number', label: 'Hours used per day', default: 4, min: 0, max: 24 },
			{ id: 'rate', type: 'number', label: 'Electricity price ($ per kWh)', default: 0.15, min: 0.01, max: 1, step: 0.01, help: 'Check a recent utility bill for your actual rate.' }
		] ),
		compute: function ( v, hw ) {
			var gpu = hw.gpu[ v.gpu ];
			var cpu = resolveCpuPower( v, hw );
			// Average draw over a session, so the rated TDP (sustained) is the
			// right basis here — the turbo peak is short-lived.
			var avgWatts = ( cpu.tdp + gpu.tdp_w + 50 ) * 0.65; // ~65% average load while actively used.
			var dailyKwh = ( avgWatts * v.hoursPerDay ) / 1000;
			var monthlyCost = dailyKwh * 30 * v.rate;
			var yearlyCost = dailyKwh * 365 * v.rate;
			var rows = [
				[ 'Estimated average draw while in use', fmt( avgWatts, 0 ) + 'W' ],
				[ 'Daily energy use', fmt( dailyKwh, 2 ) + ' kWh' ],
				[ 'Estimated monthly cost', '$' + fmt( monthlyCost, 2 ) ],
				[ 'Estimated yearly cost', '$' + fmt( yearlyCost, 2 ) ]
			];
			return resultBlock( 'At ' + v.hoursPerDay + ' hours/day, this build costs roughly <strong>$' + fmt( monthlyCost, 2 ) + '/month</strong> to run.', rows );
		}
	};

	/* ---- 4. PC Build Cost Calculator ---- */
	CS_DEFS['build-cost'] = {
		fields: [
			{ id: 'cpu', type: 'number', label: 'CPU price ($)', default: 0, min: 0, max: 5000, required: false },
			{ id: 'gpu', type: 'number', label: 'GPU price ($)', default: 0, min: 0, max: 5000, required: false },
			{ id: 'motherboard', type: 'number', label: 'Motherboard price ($)', default: 0, min: 0, max: 2000, required: false },
			{ id: 'ram', type: 'number', label: 'RAM price ($)', default: 0, min: 0, max: 1500, required: false },
			{ id: 'storage', type: 'number', label: 'Storage price ($)', default: 0, min: 0, max: 3000, required: false },
			{ id: 'psu', type: 'number', label: 'PSU price ($)', default: 0, min: 0, max: 1000, required: false },
			{ id: 'caseP', type: 'number', label: 'Case price ($)', default: 0, min: 0, max: 1000, required: false },
			{ id: 'cooler', type: 'number', label: 'Cooler price ($)', default: 0, min: 0, max: 1000, required: false },
			{ id: 'extras', type: 'number', label: 'Peripherals / monitor / other ($)', default: 0, min: 0, max: 10000, required: false }
		],
		compute: function ( v ) {
			var total = v.cpu + v.gpu + v.motherboard + v.ram + v.storage + v.psu + v.caseP + v.cooler + v.extras;
			var rows = [
				[ 'CPU', '$' + fmt( v.cpu, 2 ) ], [ 'GPU', '$' + fmt( v.gpu, 2 ) ], [ 'Motherboard', '$' + fmt( v.motherboard, 2 ) ],
				[ 'RAM', '$' + fmt( v.ram, 2 ) ], [ 'Storage', '$' + fmt( v.storage, 2 ) ], [ 'PSU', '$' + fmt( v.psu, 2 ) ],
				[ 'Case', '$' + fmt( v.caseP, 2 ) ], [ 'Cooler', '$' + fmt( v.cooler, 2 ) ], [ 'Extras', '$' + fmt( v.extras, 2 ) ]
			];
			return resultBlock( 'Estimated total build cost: <strong>$' + fmt( total, 2 ) + '</strong>', rows, false );
		}
	};

	/* ---- 5. FPS Calculator ---- */
	CS_DEFS.fps = {
		share: true,
		comboLink: true,
		fields: [
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', label: 'CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU', icon: 'gpu' },
			{ id: 'resolution', type: 'select', label: 'Resolution', icon: 'resolution', default: '1080p', options: [
				{ value: '1080p', label: '1080p (Full HD)' }, { value: '1440p', label: '1440p (QHD)' }, { value: '4k', label: '4K (UHD)' }
			] },
			{ id: 'preset', type: 'select', label: 'Graphics settings', default: 'high', options: [
				{ value: 'low', label: 'Low' }, { value: 'medium', label: 'Medium' }, { value: 'high', label: 'High' }, { value: 'ultra', label: 'Ultra / Max' }
			] },
			{ id: 'game', type: 'select', label: 'Game (optional)', default: '', options: gameOptions(), help: 'Adds that game\'s officially published minimum/recommended specs next to the estimate.' }
		],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ], gpu = hw.gpu[ v.gpu ];
			// Phase 2 (Oct 2026): uses the bottleneck engine's own resolution
			// factors and CPU parity, so at "high" settings the FPS calculator's
			// limiting part always matches the bottleneck calculator's. The CPU
			// ceiling no longer rises with resolution (it used to be 2.4x / 3.0x /
			// 4.2x the CPU score, which let the GPU be the limit almost
			// everywhere): a CPU prepares the same frames per second whatever the
			// resolution. FPS_PER_POINT (2.5 FPS per GPU score point at 1080p
			// high, was 1.8) re-anchors the scale to published 1080p averages,
			// e.g. an RTX 4070 at roughly 135-140 FPS when nothing else limits it.
			var FPS_PER_POINT = 2.5;
			var resMult = BN_GPU_RES_FACTOR[ v.resolution ];
			var presetMult = { low: 1.3, medium: 1.1, high: 1.0, ultra: 0.82 }[ v.preset ];
			var gpuFps = gpu.score * FPS_PER_POINT * resMult * presetMult;
			var cpuCap = cpu.score * BN_CPU_PARITY * FPS_PER_POINT;
			var estimate = Math.min( gpuFps, cpuCap );
			// The FPS calculator page says the range is wider when CPU and GPU
			// both matter and narrower when one part clearly dominates; it used to
			// be a flat ±18%. Now ±22% when the two ceilings are within 15% of
			// each other, ±15% otherwise (still "roughly ±18%" as /methodology/ says).
			var closeness = Math.min( gpuFps, cpuCap ) / Math.max( gpuFps, cpuCap );
			var spread = closeness >= 0.85 ? 0.22 : 0.15;
			var low = Math.round( estimate * ( 1 - spread ) );
			var high = Math.round( estimate * ( 1 + spread ) );
			// Same "under 6% = balanced" rule as the bottleneck calculator.
			var limiter = Math.round( ( 1 - closeness ) * 100 ) < 6 ? 'Balanced (CPU and GPU about even)' : ( gpuFps < cpuCap ? gpu.name + ' (GPU-bound)' : cpu.name + ' (CPU-bound)' );
			var rows = [
				[ 'Estimated FPS range', low + ' – ' + high + ' FPS' ],
				[ 'Midpoint estimate', Math.round( estimate ) + ' FPS' ],
				[ 'Likely limiting part', limiter ]
			];
			// Game selection (Sep 2026 audit): shows the publisher's own
			// minimum/recommended specs from data/games-data.php beside the
			// estimate. It deliberately does NOT scale the FPS number per
			// game — that would need per-game benchmark data we haven't
			// measured, and inventing a per-game multiplier would be a
			// fabricated figure presented as a result.
			var game = v.game && GAMES[ v.game ] ? GAMES[ v.game ] : null;
			if ( game ) {
				rows.push( [ game.name + ' — publisher minimum', escapeHtml( game.min.cpu + ' · ' + game.min.gpu + ' · ' + game.min.ram + 'GB RAM' ) ] );
				rows.push( [ game.name + ' — publisher recommended', escapeHtml( game.rec.cpu + ' · ' + game.rec.gpu + ' · ' + game.rec.ram + 'GB RAM' ) ] );
				rows.push( [ 'Full requirements', '<a href="' + escapeHtml( game.url ) + '">' + escapeHtml( game.name ) + ' system requirements</a>' ] );
			}
			return resultBlock( 'Estimated average: <strong>' + Math.round( estimate ) + ' FPS</strong> at ' + RES_LABELS[ v.resolution ] + ', ' + v.preset + ' settings' + ( game ? ' — a general estimate for this hardware, not a ' + escapeHtml( game.name ) + '-specific benchmark' : '' ) + '.', rows, 'actual FPS varies significantly by game engine, optimization, RAM speed and drivers.' );
		}
	};

	/* ---- 6. RAM Calculator ---- */
	CS_DEFS.ram = {
		fields: [
			{ id: 'usage', type: 'select', label: 'Main use case', icon: 'ram', default: 'gaming', options: [
				{ value: 'basic', label: 'Web browsing / office work' },
				{ value: 'gaming', label: 'Gaming' },
				{ value: 'streaming', label: 'Gaming + streaming/recording' },
				{ value: 'creation', label: 'Video/photo editing & content creation' },
				{ value: 'dev', label: 'Programming / development' },
				{ value: 'heavy', label: 'Virtual machines / heavy multitasking' }
			] },
			{ id: 'multitasking', type: 'select', label: 'How many apps/browser tabs do you keep open?', default: 'medium', options: [
				{ value: 'low', label: 'Just a few' }, { value: 'medium', label: 'A moderate amount' }, { value: 'high', label: 'A lot, all the time' }
			] }
		],
		compute: function ( v ) {
			var base = { basic: 8, gaming: 16, streaming: 16, creation: 32, dev: 16, heavy: 32 }[ v.usage ];
			var add = { low: 0, medium: 4, high: 8 }[ v.multitasking ];
			var recommended = base + add;
			var kits = [ 8, 16, 32, 64, 128 ];
			var suggestedKit = kits.find( function ( k ) { return k >= recommended; } ) || 128;
			// Sep 2026 audit: the raw intermediate figure (e.g. "20 GB") used
			// to be shown as "Recommended RAM" next to the real answer
			// ("32 GB kit"), which read as two conflicting recommendations —
			// RAM isn't sold in 20 GB kits. Only the purchasable kit size is
			// shown now.
			var rows = [
				[ 'Recommended RAM (kit size)', suggestedKit + ' GB' ],
				[ 'Typical setup', ( suggestedKit / 2 ) + ' GB × 2 sticks (dual channel)' ]
			];
			return resultBlock( 'We recommend at least <strong>' + suggestedKit + 'GB</strong> of RAM for this use case.', rows );
		}
	};

	/* ---- 7. PC Upgrade Calculator ---- */
	CS_DEFS.upgrade = {
		fields: [
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', label: 'Current CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'Current GPU', icon: 'gpu' },
			{ id: 'ram', type: 'number', label: 'Current RAM (GB)', default: 16, min: 4, max: 256 },
			{ id: 'storageType', type: 'select', label: 'Primary storage type', default: 'nvme', options: [
				{ value: 'hdd', label: 'Hard disk drive (HDD)' }, { value: 'sata-ssd', label: 'SATA SSD' }, { value: 'nvme', label: 'NVMe SSD' }
			] },
			{ id: 'target', type: 'select', label: 'What do you want to do?', default: '1440p', options: [
				{ value: '1080p', label: 'Smooth 1080p gaming' }, { value: '1440p', label: '1440p gaming' }, { value: '4k', label: '4K gaming' }, { value: 'productivity', label: 'Productivity / creative work' }
			] }
		],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ], gpu = hw.gpu[ v.gpu ];
			// Targets re-set with the Phase 2 score recalibration (Oct 2026).
			// GPU targets = the lowest score among cards this site's own tier
			// labels (data/hardware-data.php 'tier') call suitable for that
			// resolution, so the advisor never calls a "1440p" card too weak for
			// 1440p (the old 60 failed the RTX 4070, the old 80 failed the RTX
			// 4080 at 4K). CPU targets follow the new CPU scale.
			var targetGpuScore = { '1080p': 33, '1440p': 50, '4k': 70, productivity: 55 }[ v.target ];
			var targetCpuScore = { '1080p': 55, '1440p': 53, '4k': 50, productivity: 59 }[ v.target ];
			var priorities = [];
			if ( v.storageType === 'hdd' ) {
				priorities.push( 'Storage — moving to an NVMe SSD is the single biggest everyday speed upgrade you can make.' );
			}
			if ( v.ram < 16 ) {
				priorities.push( 'RAM — ' + v.ram + 'GB is tight for modern games and multitasking; 16GB is the current sensible minimum.' );
			}
			if ( gpu.score < targetGpuScore ) {
				priorities.push( 'GPU — the ' + gpu.name + ' is below the performance level typically needed for your target (' + v.target + ').' );
			}
			if ( cpu.score < targetCpuScore ) {
				priorities.push( 'CPU — the ' + cpu.name + ' may limit frame rates in CPU-heavy titles for your target.' );
			}
			if ( ! priorities.length ) {
				priorities.push( 'Your current CPU, GPU, RAM and storage already look well suited to this target — no upgrade is strictly necessary yet.' );
			}
			var html = '<div class="cs-result-block"><p class="cs-result-headline">Suggested upgrade priority:</p><ol class="cs-priority-list">';
			priorities.forEach( function ( p ) { html += '<li>' + escapeHtml( p ) + '</li>'; } );
			html += '</ol><p class="cs-disclaimer"><strong>Estimate only:</strong> based on typical requirements for your stated goal, not your exact game library.</p></div>';
			return html;
		}
	};

	/* ---- 8. PC Compatibility Checker ---- */
	/*
	 * Memory type each socket can physically run (Sep 2026 audit fix). The
	 * checker used to compare only "RAM you have" against "RAM the board
	 * supports" as two free choices, so AM5 + DDR4 + DDR4 read as
	 * "compatible" even though no AM5 board takes DDR4. Sources, checked
	 * Sep 2026:
	 *  - AM4: DDR4 only — en.wikipedia.org/wiki/Socket_AM4 ("Memory support: DDR4")
	 *  - AM5: DDR5 only — amd.com/en/products/processors/chipsets/am5.html
	 *  - LGA 1200: DDR4 only — en.wikipedia.org/wiki/LGA_1200
	 *  - LGA 1700: DDR4 OR DDR5, board-dependent (each board supports one) —
	 *    en.wikipedia.org/wiki/LGA_1700 ("Memory support: DDR4; DDR5")
	 *  - LGA 1851 (Core Ultra 200S): DDR5 only — en.wikipedia.org/wiki/LGA_1851
	 *    ("LGA 1851 only supports DDR5 SDRAM, dropping support for DDR4")
	 */
	var SOCKET_MEMORY = { AM4: [ 'DDR4' ], AM5: [ 'DDR5' ], LGA1200: [ 'DDR4' ], LGA1700: [ 'DDR4', 'DDR5' ], LGA1851: [ 'DDR5' ] };
	var SOCKET_OPTIONS = [
		{ value: 'AM4', label: 'AMD AM4' }, { value: 'AM5', label: 'AMD AM5' }, { value: 'LGA1851', label: 'Intel LGA 1851 (Core Ultra 200S)' }, { value: 'LGA1700', label: 'Intel LGA 1700' }, { value: 'LGA1200', label: 'Intel LGA 1200' }
	];
	CS_DEFS.compatibility = {
		fields: [
			{ id: 'cpuSocket', type: 'select', label: 'CPU socket', default: 'AM5', options: SOCKET_OPTIONS },
			{ id: 'moboSocket', type: 'select', label: 'Motherboard socket', default: 'AM5', options: SOCKET_OPTIONS },
			{ id: 'ramType', type: 'select', label: 'RAM type you have/want', default: 'DDR5', options: [ { value: 'DDR4', label: 'DDR4' }, { value: 'DDR5', label: 'DDR5' } ] },
			{ id: 'moboRam', type: 'select', label: 'Motherboard supported RAM', default: 'DDR5', options: [ { value: 'DDR4', label: 'DDR4' }, { value: 'DDR5', label: 'DDR5' } ] },
			{ id: 'gpuLength', type: 'number', label: 'GPU length (mm)', default: 320, min: 100, max: 450 },
			{ id: 'caseMaxLength', type: 'number', label: 'Case max GPU length (mm)', default: 380, min: 100, max: 600 },
			{ id: 'psuWattage', type: 'number', label: 'PSU wattage', default: 650, min: 150, max: 1600 },
			{ id: 'systemNeed', type: 'number', label: 'Estimated system power need (watts)', default: 450, min: 50, max: 1500 }
		],
		compute: function ( v ) {
			var checks = [];
			checks.push( [ 'CPU socket vs motherboard socket', v.cpuSocket === v.moboSocket, v.cpuSocket === v.moboSocket ? 'Match' : 'Mismatch — ' + v.cpuSocket + ' CPU will not fit a ' + v.moboSocket + ' motherboard.' ] );
			var socketMem = SOCKET_MEMORY[ v.moboSocket ] || [ 'DDR4', 'DDR5' ];
			var boardRamPossible = socketMem.indexOf( v.moboRam ) !== -1;
			checks.push( [ 'Motherboard socket vs its listed RAM type', boardRamPossible, boardRamPossible ? 'Valid' : 'Not possible — no ' + v.moboSocket + ' motherboard supports ' + v.moboRam + ' (' + v.moboSocket + ' boards are ' + socketMem.join( ' / ' ) + ' only). Double-check the board\'s spec page.' ] );
			var ramFitsSocket = socketMem.indexOf( v.ramType ) !== -1;
			var ramOk = ramFitsSocket && v.ramType === v.moboRam;
			var ramMsg;
			if ( ! ramFitsSocket ) {
				ramMsg = 'Mismatch — ' + v.moboSocket + ' motherboards support ' + socketMem.join( ' / ' ) + ' only, so ' + v.ramType + ' modules will not fit.';
			} else if ( v.ramType !== v.moboRam ) {
				ramMsg = 'Mismatch — ' + v.ramType + ' modules will not fit/run in a ' + v.moboRam + ' board' + ( socketMem.length > 1 ? ' (' + v.moboSocket + ' boards come in DDR4 or DDR5 versions, never both — check which one yours is).' : '.' );
			} else {
				ramMsg = 'Match';
			}
			checks.push( [ 'RAM type vs motherboard support', ramOk, ramMsg ] );
			checks.push( [ 'GPU length vs case clearance', v.gpuLength <= v.caseMaxLength, v.gpuLength <= v.caseMaxLength ? 'Fits (' + ( v.caseMaxLength - v.gpuLength ) + 'mm clearance)' : 'Too long by ' + ( v.gpuLength - v.caseMaxLength ) + 'mm.' ] );
			checks.push( [ 'PSU headroom', v.psuWattage >= v.systemNeed * 1.2, v.psuWattage >= v.systemNeed * 1.2 ? 'Sufficient headroom' : 'Tight — aim for at least ' + Math.ceil( ( v.systemNeed * 1.3 ) / 50 ) * 50 + 'W.' ] );

			var allPass = checks.every( function ( c ) { return c[ 1 ]; } );
			var rows = checks.map( function ( c ) { return [ c[ 0 ], c[ 2 ] ]; } );
			return resultBlock( allPass ? '<strong>✅ These components look compatible.</strong>' : '<strong>⚠ One or more compatibility issues found — see below.</strong>', rows );
		}
	};

	/* ---- 9. GPU VRAM Calculator ---- */
	CS_DEFS.vram = {
		fields: [
			{ id: 'resolution', type: 'select', label: 'Resolution', icon: 'resolution', default: '1440p', options: [
				{ value: '1080p', label: '1080p' }, { value: '1440p', label: '1440p' }, { value: '4k', label: '4K' }
			] },
			{ id: 'quality', type: 'select', label: 'Texture quality', default: 'high', options: [
				{ value: 'medium', label: 'Medium' }, { value: 'high', label: 'High' }, { value: 'ultra', label: 'Ultra' }
			] },
			{ id: 'rayTracing', type: 'checkbox', label: 'I want ray tracing enabled', default: false },
			{ id: 'futureProof', type: 'checkbox', label: 'I want this to hold up for 3+ years', default: false }
		],
		compute: function ( v, hw ) {
			var base = { '1080p': { medium: 4, high: 6, ultra: 8 }, '1440p': { medium: 6, high: 8, ultra: 10 }, '4k': { medium: 8, high: 12, ultra: 14 } };
			var needed = base[ v.resolution ][ v.quality ];
			if ( v.rayTracing ) needed += 2;
			if ( v.futureProof ) needed += 2;
			var matches = Object.keys( hw.gpu ).filter( function ( k ) { return hw.gpu[ k ].vram_gb >= needed; } )
				.sort( function ( a, b ) { return hw.gpu[ a ].vram_gb - hw.gpu[ b ].vram_gb; } )
				.slice( 0, 5 )
				.map( function ( k ) { return hw.gpu[ k ].name + ' (' + hw.gpu[ k ].vram_gb + 'GB)'; } );
			var rows = [ [ 'Recommended minimum VRAM', needed + ' GB' ] ];
			if ( matches.length ) {
				rows.push( [ 'GPUs that meet this', matches.join( ', ' ) ] );
			}
			return resultBlock( 'We recommend at least <strong>' + needed + 'GB</strong> of VRAM for this scenario.', rows );
		}
	};

	/* ---- 10. CPU vs GPU Calculator ---- */
	CS_DEFS['cpu-vs-gpu'] = {
		fields: [
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', label: 'CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU', icon: 'gpu' }
		],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ], gpu = hw.gpu[ v.gpu ];
			var all = bottleneckEstimate( cpu.score, gpu.score );
			var rows = [
				[ cpu.name + ' score', fmt( cpu.score, 0 ) + ' / ' + fmt( hwScoreMax( hw.cpu ), 0 ) ],
				[ gpu.name + ' score', fmt( gpu.score, 0 ) + ' / ' + fmt( hwScoreMax( hw.gpu ), 0 ) ],
				[ '1080p balance', bnLabel( all[ '1080p' ] ) ],
				[ '1440p balance', bnLabel( all[ '1440p' ] ) ],
				[ '4K balance', bnLabel( all[ '4k' ] ) ]
			];
			// Phase 2 (Oct 2026): CPU and GPU scores are separate scales, so the
			// headline comes from the per-resolution engine, not from comparing
			// the two raw scores.
			var headline = bnShiftNote( all );
			if ( ! headline ) {
				var c = all[ '1080p' ].component;
				headline = c === 'balanced' ? 'This pair is well matched at every resolution.' : ( c === 'cpu' ? 'The CPU is the more likely limit at every resolution.' : 'The GPU is the more likely limit at every resolution.' );
			}
			return resultBlock( 'For this pair: ' + headline + ' See the resolution breakdown below.', rows );
		}
	};

	/* ---- 11. CPU Performance Calculator ---- */
	CS_DEFS['cpu-performance'] = {
		fields: [ { id: 'cpu', type: 'select-hardware', source: 'cpu', label: 'CPU' } ],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ];
			// Thresholds re-set for the Phase 2 gaming-index scale (Oct 2026).
			var tier = cpu.score >= 77 ? 'Flagship' : cpu.score >= 66 ? 'High-end' : cpu.score >= 54 ? 'Mid-range' : 'Entry-level';
			var rows = [
				[ 'Performance score', fmt( cpu.score, 0 ) + ' / ' + fmt( hwScoreMax( hw.cpu ), 0 ) ], [ 'Tier', tier ],
				[ 'Cores / Threads', cpu.cores + ' / ' + cpu.threads ], [ 'Base / Boost clock', cpu.base_clock_ghz + ' GHz / ' + cpu.boost_clock_ghz + ' GHz' ],
				[ 'TDP', cpu.tdp_w + 'W' ], [ 'Socket', cpu.socket ]
			];
			return resultBlock( cpu.name + ' is a <strong>' + tier + '</strong> CPU.', rows, 'the score is a gaming-performance index; heavily multi-threaded work can rank CPUs differently.' );
		}
	};

	/* ---- 12. GPU Performance Calculator ---- */
	CS_DEFS['gpu-performance'] = {
		fields: [ { id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU' } ],
		compute: function ( v, hw ) {
			var gpu = hw.gpu[ v.gpu ];
			var rows = [
				[ 'Performance score', fmt( gpu.score, 0 ) + ' / ' + fmt( hwScoreMax( hw.gpu ), 0 ) ], [ 'Tier', gpu.tier ],
				[ 'VRAM', gpu.vram_gb + 'GB ' + gpu.memory_type ], [ 'Memory bus', gpu.memory_bus + '-bit' ],
				[ 'TDP', gpu.tdp_w + 'W' ], [ 'Recommended PSU', gpu.recommended_psu_w + 'W+' ]
			];
			return resultBlock( gpu.name + ' is rated as <strong>' + gpu.tier + '</strong>.', rows );
		}
	};

	/* ---- 13. PC Cooling Calculator ---- */
	CS_DEFS.cooling = {
		fields: [
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU', icon: 'gpu' }
		].concat( cpuPickerFields( 'Other CPU: max turbo/boost power in watts (only used with "Other CPU")', 'Intel lists this as "Maximum Turbo Power"; for AMD Ryzen use the PPT figure (about TDP × 1.35).' ) ).concat( [
			{ id: 'caseSize', type: 'select', label: 'Case size', default: 'mid', options: [
				{ value: 'compact', label: 'Small form factor / compact' }, { value: 'mid', label: 'Mid tower' }, { value: 'full', label: 'Full tower' }
			] },
			{ id: 'overclock', type: 'checkbox', label: 'I plan to overclock', default: false }
		] ),
		compute: function ( v, hw ) {
			var gpu = hw.gpu[ v.gpu ];
			var cpu = resolveCpuPower( v, hw );
			// Cooler sized for the CPU's maximum turbo/boost power — on most
			// boards unlocked chips hold that limit for sustained all-core
			// loads, so a cooler rated only for base TDP runs out of headroom.
			// Tiers recalibrated from the old TDP-based 65/125W cut-offs to
			// max-power equivalents (65W-TDP parts sit at 88–148W max power).
			var cpuHeat = cpu.max;
			var totalTdp = cpuHeat + gpu.tdp_w + ( v.overclock ? 30 : 0 );
			var cpuCooler;
			if ( cpuHeat <= 120 && ! v.overclock ) {
				cpuCooler = 'a good tower air cooler (single tower is enough)';
			} else if ( cpuHeat <= 200 ) {
				cpuCooler = 'a large dual-tower air cooler or a 240mm AIO liquid cooler';
			} else {
				cpuCooler = 'a 280–360mm AIO liquid cooler, or a top-tier dual-tower air cooler';
			}
			var caseFans = v.caseSize === 'compact' ? '2–3 fans (limited by case size — prioritize intake)' : v.caseSize === 'mid' ? '3–5 fans (2–3 intake, 1–2 exhaust)' : '5–7 fans for consistent front-to-back airflow';
			var rows = [
				[ 'Combined CPU+GPU heat load', fmt( totalTdp, 0 ) + 'W' ],
				[ 'CPU heat used (' + cpu.name + ', max turbo/boost power)', cpu.max + 'W' ],
				[ 'Recommended CPU cooler', cpuCooler ],
				[ 'Recommended case fan setup', caseFans ]
			];
			return resultBlock( 'Based on a combined heat load of <strong>' + fmt( totalTdp, 0 ) + 'W</strong>, here is what we recommend:', rows );
		}
	};

	/* ---- 14 & 15. CPU / GPU Power Consumption Calculators ---- */
	function powerConsumptionDef( source, defaultKeyPrefix ) {
		return {
			fields: [
				{ id: 'part', type: 'select-hardware', source: source, label: source === 'cpu' ? 'CPU' : 'GPU' },
				{ id: 'load', type: 'select', label: 'Typical load', default: 'gaming', options: [
					{ value: 'idle', label: 'Idle / light use (~15%)' }, { value: 'gaming', label: 'Gaming / normal use (~70%)' }, { value: 'full', label: 'Full load / benchmark (~100%+ boost)' }
				] }
			],
			compute: function ( v, hw ) {
				var part = hw[ source ][ v.part ];
				var watts;
				// Fixed during the Sep 2026 audit: "full load" used to be a
				// flat tdp_w * 1.08 for every part. For CPUs that badly
				// understated real full-load draw on unlocked Intel K-series
				// chips, where Intel's own "Maximum Turbo Power" spec runs far
				// above the "Processor Base Power" (tdp_w) — e.g. i9-13900K:
				// 125W base vs 253W max turbo, not ~135W. Real, sourced
				// max_power_w figures (see data/hardware-data.php) now drive
				// "full load" for CPUs. GPUs keep the tdp_w * 1.08 estimate,
				// since a GPU's tdp_w already represents near-peak board
				// power rather than a separate lower "base" rating.
				if ( 'full' === v.load && 'cpu' === source && part.max_power_w ) {
					watts = part.max_power_w;
				} else {
					var factor = { idle: 0.15, gaming: 0.7, full: 1.08 }[ v.load ];
					watts = part.tdp_w * factor;
				}
				var rows = [
					[ 'Rated TDP', part.tdp_w + 'W' ],
					[ 'Estimated draw at this load', fmt( watts, 0 ) + 'W' ]
				];
				if ( 'full' === v.load && 'cpu' === source && part.max_power_w ) {
					rows.push( [ 'Manufacturer’s max turbo/boost power spec', part.max_power_w + 'W' ] );
				}
				return resultBlock( 'At ' + v.load + ' load, the ' + part.name + ' draws roughly <strong>' + fmt( watts, 0 ) + 'W</strong>.', rows );
			}
		};
	}
	CS_DEFS['cpu-power'] = powerConsumptionDef( 'cpu' );
	CS_DEFS['gpu-power'] = powerConsumptionDef( 'gpu' );

	/* ---- 16. SSD Lifespan / TBW Calculator ---- */
	CS_DEFS['ssd-lifespan'] = {
		fields: [
			{ id: 'tbw', type: 'number', label: 'Drive TBW rating (TB)', default: 300, min: 10, max: 5000, help: 'Find this on the SSD\'s spec sheet, e.g. "300 TBW".' },
			{ id: 'dailyWrites', type: 'number', label: 'Estimated data written per day (GB)', default: 20, min: 1, max: 2000 }
		],
		compute: function ( v ) {
			var years = ( v.tbw * 1000 ) / ( v.dailyWrites * 365 );
			var rows = [
				[ 'Rated endurance', v.tbw + ' TBW' ],
				[ 'Estimated lifespan at this write rate', fmt( years, 1 ) + ' years' ]
			];
			return resultBlock( 'At ' + v.dailyWrites + 'GB/day, this SSD is rated to last roughly <strong>' + fmt( years, 1 ) + ' years</strong>.', rows, 'TBW is a warranty floor, not a hard cutoff — most SSDs keep working well beyond their rated TBW.' );
		}
	};

	/* ---- 16b. SSD TBW Calculator (Sep 2026 audit) ----
	 * The SSD TBW page used to mount the SSD Lifespan engine, so the two
	 * pages were the same tool. This is its own converter, built on the
	 * standard endurance relationship manufacturers publish:
	 *   DWPD = TBW × 1000 / (365 × warranty years × capacity in GB)
	 * (Kingston, "Understanding SSD endurance: TBW and DWPD", checked Sep
	 * 2026 — their 7.68TB / 14,016 TBW / 5-year example = 1 DWPD), which
	 * rearranges to TBW = DWPD × capacity(GB) × 365 × years ÷ 1000.
	 */
	CS_DEFS['ssd-tbw'] = {
		fields: [
			{ id: 'mode', type: 'select', label: 'What do you have?', default: 'dwpd', options: [
				{ value: 'dwpd', label: 'A DWPD rating — convert it to TBW' },
				{ value: 'tbw', label: 'A TBW rating — convert it to DWPD' }
			] },
			{ id: 'capacity', type: 'number', label: 'Drive capacity (GB)', default: 2000, min: 16, max: 128000, help: 'Use the marketed capacity, e.g. 1000 for a 1TB drive.' },
			{ id: 'years', type: 'number', label: 'Warranty period (years)', default: 5, min: 1, max: 10 },
			{ id: 'dwpd', type: 'number', label: 'DWPD rating (used for DWPD → TBW)', default: 0.3, min: 0.01, max: 100 },
			{ id: 'tbw', type: 'number', label: 'TBW rating (used for TBW → DWPD)', default: 1200, min: 1, max: 100000 }
		],
		compute: function ( v ) {
			var days = 365 * v.years;
			var tbw, dwpd;
			if ( 'tbw' === v.mode ) {
				tbw = v.tbw;
				dwpd = ( tbw * 1000 ) / ( days * v.capacity );
			} else {
				dwpd = v.dwpd;
				tbw = ( dwpd * v.capacity * days ) / 1000;
			}
			var gbPerDay = ( tbw * 1000 ) / days;
			var tbwPerTb = tbw / ( v.capacity / 1000 );
			var rows = [
				[ 'Endurance (TBW)', fmt( tbw, 0 ) + ' TB written' ],
				[ 'Drive writes per day (DWPD)', fmt( dwpd, 2 ) ],
				[ 'Rated writes per day', fmt( gbPerDay, 0 ) + ' GB/day for ' + v.years + ' year' + ( v.years === 1 ? '' : 's' ) ],
				[ 'TBW per TB of capacity', fmt( tbwPerTb, 0 ) + ' TBW/TB (use this to compare drives of different sizes)' ]
			];
			var headline = 'tbw' === v.mode
				? fmt( v.tbw, 0 ) + ' TBW on a ' + fmt( v.capacity, 0 ) + 'GB drive over ' + v.years + ' years = <strong>' + fmt( dwpd, 2 ) + ' DWPD</strong>'
				: fmt( v.dwpd, 2 ) + ' DWPD on a ' + fmt( v.capacity, 0 ) + 'GB drive over ' + v.years + ' years = <strong>' + fmt( tbw, 0 ) + ' TBW</strong>';
			return resultBlock( headline, rows, 'TBW and DWPD are warranty endurance ratings, not a guaranteed failure point — to see how many years a TBW rating lasts at your own write volume, use the SSD Lifespan Calculator.' );
		}
	};

	/* ---- 17. Storage / Drive Space Calculator ---- */
	CS_DEFS.storage = {
		fields: [
			{ id: 'fileSizeMb', type: 'number', label: 'Average file size (MB)', default: 500, min: 0.01, max: 1000000 },
			{ id: 'fileCount', type: 'number', label: 'Number of files', default: 100, min: 1, max: 10000000 },
			{ id: 'driveCapacityGb', type: 'number', label: 'Drive capacity (GB)', default: 1000, min: 1, max: 200000 },
			{ id: 'usedGb', type: 'number', label: 'Already used on drive (GB)', default: 0, min: 0, max: 200000, required: false }
		],
		compute: function ( v ) {
			var neededGb = ( v.fileSizeMb * v.fileCount ) / 1000;
			var remaining = v.driveCapacityGb - v.usedGb - neededGb;
			var rows = [
				[ 'Space required', fmt( neededGb, 2 ) + ' GB' ],
				[ 'Remaining after copy', fmt( remaining, 2 ) + ' GB' ]
			];
			var headline = remaining >= 0 ? 'This will fit, with <strong>' + fmt( remaining, 2 ) + 'GB</strong> free afterward.' : 'This will <strong>not fit</strong> — you are short by ' + fmt( Math.abs( remaining ), 2 ) + 'GB.';
			return resultBlock( headline, rows );
		}
	};

	/* ---- 18. Data Storage Converter ---- */
	var STORAGE_UNITS = { bit: 1 / 8, byte: 1, KB: 1e3, MB: 1e6, GB: 1e9, TB: 1e12, PB: 1e15 };
	// Binary (1,024-based) sizes, which the converter page offers alongside
	// decimal: shown with the IEC names (KiB, MiB...) so the two never mix.
	var STORAGE_UNITS_BIN = { bit: 1 / 8, byte: 1, KB: 1024, MB: Math.pow( 1024, 2 ), GB: Math.pow( 1024, 3 ), TB: Math.pow( 1024, 4 ), PB: Math.pow( 1024, 5 ) };
	var STORAGE_BIN_NAMES = { KB: 'KiB', MB: 'MiB', GB: 'GiB', TB: 'TiB', PB: 'PiB' };
	// Very small or very large results keep 4 significant digits instead of
	// rounding to 0 (e.g. 1 KB in TB).
	function fmtStorage( n ) {
		if ( n !== 0 && ( Math.abs( n ) < 0.001 || Math.abs( n ) >= 1e15 ) ) {
			return Number( n.toPrecision( 4 ) ).toExponential().replace( 'e', ' × 10^' ).replace( '^+', '^' );
		}
		return fmt( n, 4 );
	}
	CS_DEFS['storage-converter'] = {
		fields: [
			{ id: 'amount', type: 'number', label: 'Amount', default: 1, min: 0, max: 1e15 },
			{ id: 'from', type: 'select', label: 'From unit', default: 'GB', options: Object.keys( STORAGE_UNITS ).map( function ( u ) { return { value: u, label: u }; } ) },
			{ id: 'to', type: 'select', label: 'To unit', default: 'MB', options: Object.keys( STORAGE_UNITS ).map( function ( u ) { return { value: u, label: u }; } ) },
			{ id: 'standard', type: 'select', label: 'Counting system', default: 'decimal', options: [
				{ value: 'decimal', label: 'Decimal (1,000-based, drive makers)' },
				{ value: 'binary', label: 'Binary (1,024-based, KiB/MiB/GiB, Windows)' }
			] }
		],
		compute: function ( v ) {
			var binary = 'binary' === v.standard;
			var units = binary ? STORAGE_UNITS_BIN : STORAGE_UNITS;
			var name = function ( u ) { return binary && STORAGE_BIN_NAMES[ u ] ? STORAGE_BIN_NAMES[ u ] : u; };
			var bytes = v.amount * units[ v.from ];
			var result = bytes / units[ v.to ];
			var rows = Object.keys( units ).map( function ( u ) { return [ name( u ), fmtStorage( bytes / units[ u ] ) ]; } );
			return resultBlock( v.amount + ' ' + name( v.from ) + ' = <strong>' + fmtStorage( result ) + ' ' + name( v.to ) + '</strong>', rows, false );
		}
	};

	/* ---- 19 & 20 & 24. Download / Upload / File Transfer Time ---- */
	function transferTimeDef( label ) {
		return {
			fields: [
				{ id: 'size', type: 'number', label: 'File size', default: 5, min: 0.001, max: 1000000 },
				{ id: 'sizeUnit', type: 'select', label: 'Size unit', default: 'GB', options: [ { value: 'MB', label: 'MB' }, { value: 'GB', label: 'GB' }, { value: 'TB', label: 'TB' } ] },
				{ id: 'speed', type: 'number', label: label + ' speed', default: 100, min: 0.01, max: 100000 },
				{ id: 'speedUnit', type: 'select', label: 'Speed unit', default: 'Mbps', options: [ { value: 'Mbps', label: 'Mbps (megabits/sec)' }, { value: 'MBps', label: 'MB/s (megabytes/sec)' }, { value: 'Gbps', label: 'Gbps' } ] }
			],
			compute: function ( v ) {
				var sizeMB = v.size * ( { MB: 1, GB: 1000, TB: 1000000 }[ v.sizeUnit ] );
				var speedMBs = v.speedUnit === 'Mbps' ? v.speed / 8 : v.speedUnit === 'Gbps' ? ( v.speed * 1000 ) / 8 : v.speed;
				var seconds = sizeMB / speedMBs;
				var rows = [ [ 'Estimated time', secondsToHuman( seconds ) ], [ 'Effective speed', fmt( speedMBs, 2 ) + ' MB/s' ] ];
				return resultBlock( 'Estimated ' + label.toLowerCase() + ' time: <strong>' + secondsToHuman( seconds ) + '</strong>', rows, 'real-world transfer speed is usually 10–20% below your rated connection speed due to overhead.' );
			}
		};
	}
	CS_DEFS['download-time'] = transferTimeDef( 'Download' );
	CS_DEFS['upload-time'] = transferTimeDef( 'Upload' );
	/* ---- File Transfer Time with interface presets (Sep 2026 audit) ----
	 * Previously the same generic engine as Download/Upload Time. Presets
	 * below are each interface's maximum usable data rate AFTER line
	 * encoding (the "5 Gbps" on a USB box is not 625 MB/s of payload).
	 * Sources, checked Sep 2026:
	 *  - USB 2.0 High Speed: 480 Mbit/s signalling, "maximum theoretical data
 *    throughput 53 MByte/s" — en.wikipedia.org/wiki/USB_2.0.
	 *  - USB 3.2 Gen 1 (5 Gbit/s, 8b/10b): 500 MB/s; Gen 2 (10 Gbit/s,
	 *    128b/132b): 1212 MB/s; Gen 2x2 (20 Gbit/s): 2424 MB/s —
	 *    en.wikipedia.org/wiki/USB_3.0.
	 *  - SATA III (6 Gbit/s, 8b/10b): 600 MB/s — en.wikipedia.org/wiki/SATA.
	 *  - PCIe 3.0 x4: 3938 MB/s; PCIe 4.0 x4: 7877 MB/s (128b/130b) —
	 *    en.wikipedia.org/wiki/PCI_Express bandwidth table.
	 *  - Ethernet 1/2.5/10 Gbit/s: 125 / 312.5 / 1250 MB/s (Ethernet data
	 *    rates are quoted after line coding).
	 * Real transfers are then capped by the slower drive at either end,
	 * which the result says explicitly.
	 */
	var TRANSFER_PRESETS = {
		usb2: { label: 'USB 2.0 (480 Mbps)', mbs: 53 },
		usb3g1: { label: 'USB 3.2 Gen 1 / USB 3.0 (5 Gbps)', mbs: 500 },
		usb3g2: { label: 'USB 3.2 Gen 2 (10 Gbps)', mbs: 1212 },
		usb3g2x2: { label: 'USB 3.2 Gen 2x2 (20 Gbps)', mbs: 2424 },
		sata3: { label: 'SATA III internal (6 Gbps)', mbs: 600 },
		pcie3x4: { label: 'NVMe on PCIe 3.0 x4', mbs: 3938 },
		pcie4x4: { label: 'NVMe on PCIe 4.0 x4', mbs: 7877 },
		gbe: { label: 'Gigabit Ethernet (1 Gbps)', mbs: 125 },
		gbe25: { label: '2.5 Gigabit Ethernet', mbs: 312.5 },
		gbe10: { label: '10 Gigabit Ethernet', mbs: 1250 }
	};
	CS_DEFS['file-transfer-time'] = {
		fields: [
			{ id: 'size', type: 'number', label: 'File size', default: 50, min: 0.001, max: 1000000 },
			{ id: 'sizeUnit', type: 'select', label: 'Size unit', default: 'GB', options: [ { value: 'MB', label: 'MB' }, { value: 'GB', label: 'GB' }, { value: 'TB', label: 'TB' } ] },
			{ id: 'iface', type: 'select', label: 'Connection / interface', default: 'usb3g1', options: [
				{ value: 'usb2', label: TRANSFER_PRESETS.usb2.label }, { value: 'usb3g1', label: TRANSFER_PRESETS.usb3g1.label },
				{ value: 'usb3g2', label: TRANSFER_PRESETS.usb3g2.label }, { value: 'usb3g2x2', label: TRANSFER_PRESETS.usb3g2x2.label },
				{ value: 'sata3', label: TRANSFER_PRESETS.sata3.label }, { value: 'pcie3x4', label: TRANSFER_PRESETS.pcie3x4.label },
				{ value: 'pcie4x4', label: TRANSFER_PRESETS.pcie4x4.label }, { value: 'gbe', label: TRANSFER_PRESETS.gbe.label },
				{ value: 'gbe25', label: TRANSFER_PRESETS.gbe25.label }, { value: 'gbe10', label: TRANSFER_PRESETS.gbe10.label },
				{ value: 'custom', label: 'Custom speed (Wi-Fi, measured speed, etc.)' }
			] },
			{ id: 'speed', type: 'number', label: 'Custom speed (only used with "Custom")', default: 30, min: 0.01, max: 100000 },
			{ id: 'speedUnit', type: 'select', label: 'Custom speed unit', default: 'MBps', options: [ { value: 'MBps', label: 'MB/s (megabytes/sec)' }, { value: 'Mbps', label: 'Mbps (megabits/sec)' }, { value: 'Gbps', label: 'Gbps' } ] },
			{ id: 'efficiency', type: 'select', label: 'Real-world factor', default: '0.8', options: [
				{ value: '1', label: 'Theoretical maximum (100%)' },
				{ value: '0.8', label: 'Typical large-file copy (~80%)' },
				{ value: '0.5', label: 'Many small files / busy link (~50%)' }
			] }
		],
		compute: function ( v ) {
			var sizeMB = v.size * ( { MB: 1, GB: 1000, TB: 1000000 }[ v.sizeUnit ] );
			var linkMBs, linkLabel;
			if ( 'custom' === v.iface ) {
				linkMBs = v.speedUnit === 'Mbps' ? v.speed / 8 : v.speedUnit === 'Gbps' ? ( v.speed * 1000 ) / 8 : v.speed;
				linkLabel = 'Custom';
			} else {
				linkMBs = TRANSFER_PRESETS[ v.iface ].mbs;
				linkLabel = TRANSFER_PRESETS[ v.iface ].label;
			}
			var factor = parseFloat( v.efficiency );
			var effective = linkMBs * factor;
			var seconds = sizeMB / effective;
			var rows = [
				[ 'Estimated time', secondsToHuman( seconds ) ],
				[ 'Interface maximum', fmt( linkMBs, 0 ) + ' MB/s (' + linkLabel + ')' ],
				[ 'Speed used for estimate', fmt( effective, 0 ) + ' MB/s' ]
			];
			return resultBlock( 'Estimated transfer time: <strong>' + secondsToHuman( seconds ) + '</strong>', rows, 'the slowest part of the chain sets the real speed — a hard drive (roughly 80–160 MB/s, per our SSD vs HDD guide) or a budget flash drive will cap a fast interface well below its maximum.' );
		}
	};

	/* ---- 21 & 22. Mbps <-> MB/s converters ---- */
	CS_DEFS['mbps-to-mbs'] = {
		fields: [ { id: 'mbps', type: 'number', label: 'Speed (Mbps)', default: 100, min: 0, max: 100000 } ],
		compute: function ( v ) {
			var mbs = v.mbps / 8;
			return resultBlock( fmt( v.mbps, 2 ) + ' Mbps = <strong>' + fmt( mbs, 2 ) + ' MB/s</strong>', [ [ 'Megabits per second', fmt( v.mbps, 2 ) + ' Mbps' ], [ 'Megabytes per second', fmt( mbs, 2 ) + ' MB/s' ] ], false );
		}
	};
	CS_DEFS['mbs-to-mbps'] = {
		fields: [ { id: 'mbs', type: 'number', label: 'Speed (MB/s)', default: 12.5, min: 0, max: 100000 } ],
		compute: function ( v ) {
			var mbps = v.mbs * 8;
			return resultBlock( fmt( v.mbs, 2 ) + ' MB/s = <strong>' + fmt( mbps, 2 ) + ' Mbps</strong>', [ [ 'Megabytes per second', fmt( v.mbs, 2 ) + ' MB/s' ], [ 'Megabits per second', fmt( mbps, 2 ) + ' Mbps' ] ], false );
		}
	};

	/* ---- 23. Bandwidth Calculator ---- */
	CS_DEFS.bandwidth = {
		fields: [
			{ id: 'amount', type: 'number', label: 'Data amount', default: 10, min: 0.001, max: 1000000 },
			{ id: 'unit', type: 'select', label: 'Data unit', default: 'GB', options: [ { value: 'MB', label: 'MB' }, { value: 'GB', label: 'GB' }, { value: 'TB', label: 'TB' } ] },
			{ id: 'time', type: 'number', label: 'Time', default: 10, min: 0.01, max: 100000 },
			{ id: 'timeUnit', type: 'select', label: 'Time unit', default: 'minutes', options: [ { value: 'seconds', label: 'Seconds' }, { value: 'minutes', label: 'Minutes' }, { value: 'hours', label: 'Hours' } ] }
		],
		compute: function ( v ) {
			var megabits = v.amount * ( { MB: 8, GB: 8000, TB: 8000000 }[ v.unit ] );
			var seconds = v.time * ( { seconds: 1, minutes: 60, hours: 3600 }[ v.timeUnit ] );
			var mbps = megabits / seconds;
			return resultBlock( 'Required bandwidth: <strong>' + fmt( mbps, 2 ) + ' Mbps</strong>', [ [ 'Mbps', fmt( mbps, 2 ) ], [ 'MB/s', fmt( mbps / 8, 2 ) ] ] );
		}
	};

	/* ---- 25. Internet Data Usage Calculator ---- */
	CS_DEFS['data-usage'] = {
		fields: [
			{ id: 'streamingHours', type: 'number', label: 'Video streaming (hours/day)', default: 2, min: 0, max: 24, required: false },
			{ id: 'streamingQuality', type: 'select', label: 'Streaming quality', default: 'hd', options: [ { value: 'sd', label: 'SD' }, { value: 'hd', label: 'HD' }, { value: '4k', label: '4K' } ] },
			{ id: 'browsingHours', type: 'number', label: 'Browsing / social media (hours/day)', default: 2, min: 0, max: 24, required: false },
			{ id: 'gamingHours', type: 'number', label: 'Online gaming (hours/day)', default: 1, min: 0, max: 24, required: false },
			{ id: 'callHours', type: 'number', label: 'Video calls (hours/day)', default: 0.5, min: 0, max: 24, required: false }
		],
		compute: function ( v ) {
			// GB per hour. Matches Netflix's own published help-center figures
			// (SD ~1GB/hr, HD ~3GB/hr, 4K ~7GB/hr) and independent measured
			// testing (SD 0.2-0.35, HD 2.8-3.3, 4K 6.5-11.5 GB/hr) — verified
			// during the Sep 2026 audit against the old figures (0.3/1.0/2.5),
			// which understated HD and 4K usage by roughly 3x.
			var streamRate = { sd: 0.3, hd: 3.0, '4k': 7.0 }[ v.streamingQuality ];
			var dailyGb = ( v.streamingHours * streamRate ) + ( v.browsingHours * 0.06 ) + ( v.gamingHours * 0.1 ) + ( v.callHours * 0.6 );
			var monthlyGb = dailyGb * 30;
			var rows = [ [ 'Estimated daily usage', fmt( dailyGb, 2 ) + ' GB' ], [ 'Estimated monthly usage', fmt( monthlyGb, 1 ) + ' GB' ] ];
			return resultBlock( 'Estimated monthly data usage: <strong>' + fmt( monthlyGb, 1 ) + ' GB</strong>', rows, 'based on typical published per-hour data rates for each activity — your actual usage depends on the specific apps and settings you use.' );
		}
	};

	/* ---- 26/31. DPI / PPI / Pixel Density ---- */
	function densityCompute( v ) {
		var ppi = Math.sqrt( v.width * v.width + v.height * v.height ) / v.diagonal;
		var category = ppi < 100 ? 'Standard density' : ppi < 150 ? 'Sharp' : ppi < 300 ? 'High density' : 'Very high density';
		return resultBlock( 'Pixel density: <strong>' + fmt( ppi, 1 ) + ' PPI</strong> (' + category + ')', [ [ 'PPI', fmt( ppi, 1 ) ], [ 'Category', category ] ], false );
	}
	var densityFields = [
		{ id: 'diagonal', type: 'number', label: 'Screen diagonal (inches)', default: 27, min: 1, max: 200 },
		{ id: 'width', type: 'number', label: 'Horizontal resolution (px)', default: 2560, min: 1, max: 20000 },
		{ id: 'height', type: 'number', label: 'Vertical resolution (px)', default: 1440, min: 1, max: 20000 }
	];
	CS_DEFS.ppi = { fields: densityFields, compute: densityCompute };
	CS_DEFS['pixel-density'] = { fields: densityFields, compute: densityCompute };
	CS_DEFS.dpi = {
		fields: [
			{ id: 'width', type: 'number', label: 'Image width (px)', default: 3000, min: 1, max: 100000 },
			{ id: 'printWidth', type: 'number', label: 'Print width (inches)', default: 10, min: 0.1, max: 500 }
		],
		compute: function ( v ) {
			var dpi = v.width / v.printWidth;
			return resultBlock( 'Print resolution: <strong>' + fmt( dpi, 0 ) + ' DPI</strong>', [ [ 'DPI', fmt( dpi, 0 ) ], [ 'Good for print?', dpi >= 300 ? 'Yes — 300 DPI+ is standard for sharp prints.' : 'May look soft — 300 DPI+ is recommended for photo-quality prints.' ] ] );
		}
	};

	/* ---- 27/30. Screen / Monitor Size Calculator ---- */
	function screenSizeCompute( v ) {
		var parts = v.ratio.split( ':' ).map( Number );
		var rw = parts[ 0 ], rh = parts[ 1 ];
		var diag = v.diagonal;
		var widthIn = ( diag * rw ) / Math.sqrt( rw * rw + rh * rh );
		var heightIn = ( diag * rh ) / Math.sqrt( rw * rw + rh * rh );
		var rows = [
			[ 'Width', fmt( widthIn, 1 ) + ' in (' + fmt( widthIn * 2.54, 1 ) + ' cm)' ],
			[ 'Height', fmt( heightIn, 1 ) + ' in (' + fmt( heightIn * 2.54, 1 ) + ' cm)' ]
		];
		return resultBlock( 'A ' + diag + '" ' + v.ratio + ' screen is about <strong>' + fmt( widthIn, 1 ) + '" × ' + fmt( heightIn, 1 ) + '"</strong>', rows, false );
	}
	var screenSizeFields = [
		{ id: 'diagonal', type: 'number', label: 'Diagonal size (inches)', default: 27, min: 1, max: 200 },
		{ id: 'ratio', type: 'select', label: 'Aspect ratio', default: '16:9', options: [ { value: '16:9', label: '16:9' }, { value: '16:10', label: '16:10' }, { value: '21:9', label: '21:9 (ultrawide)' }, { value: '4:3', label: '4:3' }, { value: '32:9', label: '32:9 (super ultrawide)' } ] }
	];
	CS_DEFS['screen-size'] = { fields: screenSizeFields, compute: screenSizeCompute };
	CS_DEFS['monitor-size'] = { fields: screenSizeFields, compute: screenSizeCompute };

	/* ---- 28. Aspect Ratio Calculator ---- */
	// Keys must match the exact string `decimal` below produces
	// (`.toFixed(4)`, always 4 digits after the point) — '1.6' / '1' never
	// matched '1.6000' / '1.0000', so 16:10 and 1:1 silently fell through
	// to the generic simplified-fraction label instead of their named one.
	//
	// Sep 2026 audit fix: exact-decimal matching only ever named ratios that
	// simplify perfectly, so real panels marketed under a round name got
	// nonsense back — 3440×1440 showed "43:18 (43:18)" and 1366×768 showed
	// "683:384". Many common panels are sold under the nearest round ratio
	// rather than their exact one (the page copy already explains this for
	// 2560×1080 = 64:27 ≈ 21:9). Now: the exact simplified ratio is always
	// shown, and the marketed name is added when the real ratio is within 3%
	// of a common one, flagged "≈" unless it's an exact match.
	var COMMON_RATIO_LIST = [
		{ w: 1, h: 1, name: '1:1' }, { w: 5, h: 4, name: '5:4' }, { w: 4, h: 3, name: '4:3' }, { w: 3, h: 2, name: '3:2' },
		{ w: 16, h: 10, name: '16:10' }, { w: 16, h: 9, name: '16:9' }, { w: 21, h: 9, name: '21:9 (ultrawide)' }, { w: 32, h: 9, name: '32:9 (super ultrawide)' }
	];
	function describeRatio( width, height ) {
		var divisor = gcd( width, height );
		var simpleW = Math.round( width / divisor );
		var simpleH = Math.round( height / divisor );
		var actual = width / height;
		var best = null, bestDiff = Infinity;
		COMMON_RATIO_LIST.forEach( function ( r ) {
			var diff = Math.abs( actual - r.w / r.h ) / ( r.w / r.h );
			if ( diff < bestDiff ) {
				bestDiff = diff;
				best = r;
			}
		} );
		var exact = best && simpleW * best.h === simpleH * best.w;
		var marketed = best && bestDiff <= 0.03 ? ( exact ? best.name : '≈ ' + best.name ) : null;
		return { simple: simpleW + ':' + simpleH, decimal: actual.toFixed( 4 ), marketed: marketed, exact: exact, diffPct: bestDiff * 100 };
	}
	CS_DEFS['aspect-ratio'] = {
		fields: [
			{ id: 'width', type: 'number', label: 'Width (px)', default: 1920, min: 1, max: 20000 },
			{ id: 'height', type: 'number', label: 'Height (px)', default: 1080, min: 1, max: 20000 }
		],
		compute: function ( v ) {
			var r = describeRatio( v.width, v.height );
			var rows = [ [ 'Simplified ratio', r.simple ], [ 'Decimal', r.decimal + ':1' ] ];
			var headline;
			if ( r.marketed && r.exact ) {
				headline = 'Aspect ratio: <strong>' + r.simple + '</strong>';
				rows.push( [ 'Common name', r.marketed ] );
			} else if ( r.marketed ) {
				headline = 'Aspect ratio: <strong>' + r.simple + '</strong> — commonly marketed as <strong>' + r.marketed.replace( '≈ ', '' ) + '</strong>';
				rows.push( [ 'Commonly marketed as', r.marketed + ' (' + ( r.diffPct < 0.1 ? 'under 0.1%' : fmt( r.diffPct, 1 ) + '%' ) + ' off the exact ratio)' ] );
			} else {
				headline = 'Aspect ratio: <strong>' + r.simple + '</strong>';
				rows.push( [ 'Common name', 'None — not close to a standard ratio' ] );
			}
			return resultBlock( headline, rows, false );
		}
	};

	/* ---- 29. Resolution Calculator ---- */
	var RES_NAMES = { '1280x720': 'HD (720p)', '1920x1080': 'Full HD (1080p)', '2560x1440': 'QHD (1440p)', '3840x2160': '4K UHD (2160p)', '7680x4320': '8K UHD (4320p)', '2560x1600': 'WQXGA', '3440x1440': 'UWQHD (ultrawide)', '1366x768': 'WXGA' };
	CS_DEFS.resolution = {
		fields: [
			{ id: 'width', type: 'number', label: 'Width (px)', default: 1920, min: 1, max: 20000 },
			{ id: 'height', type: 'number', label: 'Height (px)', default: 1080, min: 1, max: 20000 }
		],
		compute: function ( v ) {
			var megapixels = ( v.width * v.height ) / 1e6;
			var key = v.width + 'x' + v.height;
			var name = RES_NAMES[ key ] || 'Custom resolution';
			var ratio = describeRatio( v.width, v.height );
			return resultBlock( key + ' is <strong>' + name + '</strong>', [ [ 'Total pixels', fmt( megapixels, 2 ) + ' MP' ], [ 'Aspect ratio', ratio.simple + ( ratio.marketed && ! ratio.exact ? ', ' + ratio.marketed : '' ) ] ], false );
		}
	};

	/* ---- 31 (bis). Refresh Rate Calculator ----
	 * hz/ms are mutually exclusive (see the `clears` wiring in
	 * initCalculator): typing in one auto-blanks the other, so at most one
	 * ever holds a real value when Calculate runs. The old heuristic here
	 * ("is hz still exactly the 144 default?") broke as soon as BOTH boxes
	 * held a value at once — e.g. Hz left at a leftover 240 from a
	 * previous calculation while ms was filled in — silently recomputing
	 * from the stale Hz instead of the ms the user just typed. Confirmed
	 * live during the Sep 2026 audit.
	 */
	CS_DEFS['refresh-rate'] = {
		fields: [
			{ id: 'hz', type: 'number', label: 'Refresh rate (Hz)', default: 144, min: 1, max: 1000, required: false, blankValue: '', clears: 'ms' },
			{ id: 'ms', type: 'number', label: 'Or: frame time (ms)', default: '', min: 0.01, max: 1000, required: false, blankValue: '', help: 'Fill in either field — Hz or ms — and leave the other blank.', clears: 'hz' }
		],
		compute: function ( v ) {
			// Sep 2026 audit fix: with both boxes cleared, the Hz field used
			// to fall back to its 144 default and quietly return a 144 Hz
			// result the user never asked for. Both blank is now an error.
			var hz = v.hz, ms = v.ms;
			if ( hz === '' && ms === '' ) {
				throw { msg: 'Enter either a refresh rate (Hz) or a frame time (ms) to convert.' };
			}
			if ( ms !== '' ) {
				hz = 1000 / ms;
			} else {
				ms = 1000 / hz;
			}
			return resultBlock( fmt( hz, 2 ) + ' Hz = <strong>' + fmt( ms, 2 ) + ' ms per frame</strong>', [ [ 'Refresh rate', fmt( hz, 2 ) + ' Hz' ], [ 'Frame time', fmt( ms, 2 ) + ' ms' ] ], false );
		}
	};

	/* ----------------------------------------------------------------
	 * Boot
	 * ---------------------------------------------------------------- */

	document.addEventListener( 'DOMContentLoaded', function () {
		var calcs = document.querySelectorAll( '.cs-calculator' );
		for ( var i = 0; i < calcs.length; i++ ) {
			initCalculator( calcs[ i ] );
		}
	} );
})();
