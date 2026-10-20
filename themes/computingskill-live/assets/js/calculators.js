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
		// E4 (FUN-07): always en-US, like the page text around it; the
		// browser locale turned 1234.5 into "1.234,5" next to English words.
		return Number( num.toFixed( d ) ).toLocaleString( 'en-US', { maximumFractionDigits: d } );
	}

	// E4 (FUN-07): money always shows 2 decimals ("$1,234.50", not "$1,234.5").
	function fmtMoney( num ) {
		if ( ! isFinite( num ) ) {
			return '—';
		}
		return Number( num.toFixed( 2 ) ).toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
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

	// D4 (Oct 2026, audit 1.11): the lists grew to 30+ parts each, so they
	// are grouped by brand (in data-file order) and sorted by name inside
	// each group. Hardware pickers have no `default`, so share links always
	// carry the part and the new order changes no shared result.
	function hardwareOptions( source ) {
		var data = HW[ source ] || {};
		var noun = source === 'cpu' ? ' CPUs' : ' GPUs';
		var groups = [];
		var byGroup = {};
		Object.keys( data ).forEach( function ( key ) {
			var g = ( data[ key ].brand || 'Other' ) + noun;
			if ( ! byGroup[ g ] ) {
				byGroup[ g ] = [];
				groups.push( g );
			}
			byGroup[ g ].push( { value: key, label: data[ key ].name, group: g } );
		} );
		var options = [];
		groups.forEach( function ( g ) {
			byGroup[ g ].sort( function ( a, b ) {
				return a.label.localeCompare( b.label, 'en', { numeric: true } );
			} );
			options = options.concat( byGroup[ g ] );
		} );
		return options;
	}

	// The "Other / not listed" companion picker: every part, fastest first,
	// so a reader can place an unlisted part next to one they know from a
	// review. The estimate then uses that listed part's own data.
	var OTHER_VALUE = '__other';
	function closestPartOptions( source ) {
		var data = HW[ source ] || {};
		return Object.keys( data ).sort( function ( a, b ) {
			return ( data[ b ].score - data[ a ].score ) || data[ a ].name.localeCompare( data[ b ].name, 'en', { numeric: true } );
		} ).map( function ( key ) {
			return { value: key, label: data[ key ].name };
		} );
	}

	function hardwareStart( source ) {
		return Object.keys( HW[ source ] || {} )[ 0 ] || '';
	}

	function fillSelect( select, opts ) {
		var groupEls = {};
		opts.forEach( function ( opt ) {
			var o = document.createElement( 'option' );
			o.value = opt.value;
			o.textContent = opt.label;
			if ( opt.group ) {
				if ( ! groupEls[ opt.group ] ) {
					groupEls[ opt.group ] = document.createElement( 'optgroup' );
					groupEls[ opt.group ].label = opt.group;
					select.appendChild( groupEls[ opt.group ] );
				}
				groupEls[ opt.group ].appendChild( o );
			} else {
				select.appendChild( o );
			}
		} );
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
			if ( field.type === 'select-hardware' && field.other ) {
				opts = opts.concat( [ { value: OTHER_VALUE, label: 'Other / not listed' } ] );
			}
			fillSelect( input, opts );
			if ( field.default !== undefined ) {
				input.value = field.default;
			} else if ( field.type === 'select-hardware' ) {
				// Start on the first part in the data file (Ryzen 5 5600 /
				// RTX 3060, as before the lists were sorted), not on the
				// alphabetically first, oldest part.
				input.value = hardwareStart( field.source );
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

		if ( field.type === 'select-hardware' && field.other ) {
			var noun = field.source === 'cpu' ? 'CPU' : 'GPU';
			var otherBox = document.createElement( 'div' );
			otherBox.className = 'cs-field-other';
			otherBox.hidden = true;
			var otherLabel = document.createElement( 'label' );
			otherLabel.setAttribute( 'for', inputId + '-other' );
			otherLabel.textContent = 'Closest listed ' + noun + ' in performance';
			var otherSelect = document.createElement( 'select' );
			otherSelect.id = inputId + '-other';
			// U1 (U-07): starts empty and must be picked. It used to start on
			// the fastest part (9800X3D / RTX 5090), so an unknown, likely older
			// part was estimated as the best one.
			fillSelect( otherSelect, [ { value: '', label: 'Choose the closest listed ' + noun + '…' } ].concat( closestPartOptions( field.source ) ) );
			var otherHelp = document.createElement( 'p' );
			otherHelp.className = 'cs-field-help';
			otherHelp.textContent = 'Fastest first. Pick the listed ' + noun + ' that reviews place closest to yours; the estimate uses that ' + noun + '’s data.';
			otherBox.appendChild( otherLabel );
			otherBox.appendChild( otherSelect );
			otherBox.appendChild( otherHelp );
			main.appendChild( otherBox );
			input.csOther = otherSelect;
			var syncOther = function () {
				otherBox.hidden = input.value !== OTHER_VALUE;
			};
			input.addEventListener( 'change', syncOther );
			input.csSyncOther = syncOther;
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
		if ( field.type === 'select-hardware' && input.csOther && input.value === OTHER_VALUE ) {
			var picked = input.csOther.value;
			var part = HW[ field.source ] && HW[ field.source ][ picked ];
			var noun = field.source === 'cpu' ? 'CPU' : 'GPU';
			if ( ! part ) {
				throw { msg: 'You chose "Other / not listed" for your ' + noun + ': pick the listed ' + noun + ' closest to yours in performance under "Closest listed ' + noun + ' in performance", or choose your ' + noun + ' from the main list.' };
			}
			return { value: picked, warning: 'Your ' + noun + ' isn’t in our list, so this estimate uses the closest listed ' + noun + ': ' + part.name + '.' };
		}
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

		// U1 (U-53): counts (RAM sticks, drives, fans) are whole numbers.
		if ( field.integer && num !== Math.floor( num ) ) {
			throw { msg: '"' + field.label + '" must be a whole number.' };
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
			// E4 (FUN-05): `cpu_other=1` marks a link made with "Other / not
			// listed": the part in `cpu` goes into the closest-part picker and
			// the result keeps its "isn't in our list" note.
			var otherKey = field.id + '_other';
			if ( input.csOther && params.get( otherKey ) === '1' ) {
				var stand = params.get( field.id );
				var ok = false;
				for ( var i = 0; i < input.csOther.options.length; i++ ) {
					if ( stand && input.csOther.options[ i ].value === stand ) {
						ok = true;
					}
				}
				if ( ! ok ) {
					params.delete( field.id );
					params.delete( otherKey );
					dirty = true;
					return;
				}
				input.value = OTHER_VALUE;
				input.csOther.value = stand;
				input.csSyncOther();
				applied = true;
				return;
			}
			if ( params.has( otherKey ) ) {
				params.delete( otherKey );
				dirty = true;
			}
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
			if ( input.csSyncOther ) {
				input.csSyncOther();
			}
			applied = true;
		} );
		if ( dirty ) {
			writeShareUrl( params );
		}
		return applied && ! dirty;
	}

	function updateShareUrl( def, values, inputs ) {
		var params = shareParams();
		if ( ! params ) {
			return;
		}
		def.fields.forEach( function ( field ) {
			var v = values[ field.id ];
			if ( inputs[ field.id ] && inputs[ field.id ].csOther ) {
				if ( inputs[ field.id ].value === OTHER_VALUE ) {
					params.set( field.id + '_other', '1' );
				} else {
					params.delete( field.id + '_other' );
				}
			}
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
		def.fields.forEach( function ( field ) {
			params.delete( field.id );
			params.delete( field.id + '_other' );
		} );
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
		var wrappers = {};

		def.fields.forEach( function ( field ) {
			var built = buildField( field );
			fieldsRoot.appendChild( built.wrapper );
			inputs[ field.id ] = built.input;
			wrappers[ field.id ] = built.wrapper;
		} );

		// E4 (FUN-04): a field with `showIf: { field, value }` is shown only
		// while that other field has that value (the PSU, PC Power and
		// Cooling calculators' "Other CPU" watts box). While hidden it is
		// neither read nor checked: calculate() uses its default.
		function syncShown() {
			def.fields.forEach( function ( field ) {
				if ( field.showIf && inputs[ field.showIf.field ] ) {
					wrappers[ field.id ].hidden = inputs[ field.showIf.field ].value !== field.showIf.value;
				}
			} );
		}
		def.fields.forEach( function ( field ) {
			if ( field.showIf && inputs[ field.showIf.field ] ) {
				inputs[ field.showIf.field ].addEventListener( 'change', syncShown );
			}
		} );
		syncShown();

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
					if ( field.showIf && wrappers[ field.id ].hidden ) {
						values[ field.id ] = field.default;
						return;
					}
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
				updateShareUrl( def, values, inputs );
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

		// E4 (FUN-01): only Enter in an input or select calculates; Enter on
		// a link in the result (combo page, game, methodology) follows it.
		container.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' && ( e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' ) ) {
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
					} else if ( field.type === 'select-hardware' ) {
						// value = '' left a picker empty and the next
						// Calculate failed.
						input.value = hardwareStart( field.source );
					} else {
						input.value = '';
					}
					if ( input.csOther ) {
						input.csOther.value = '';
					}
					if ( input.csSyncOther ) {
						input.csSyncOther();
					}
				} );
				syncShown();
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
			var fromLink = applyShareParams( def, inputs );
			syncShown();
			if ( fromLink ) {
				calculate();
			}
		}
		// D9: the result area's reserved height (data-shared, fixes.css) is
		// only for the share-link result on load, never after Reset.
		container.removeAttribute( 'data-shared' );
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

	// D2 (Oct 2026, audit 6.9): one wording for every result built on the
	// hardware scores, so no tool implies the scores are measured in-house.
	var SCORE_NOTE = 'the scores are ComputingSkill’s own gaming-performance index, placed from published third-party review averages. We don’t benchmark parts ourselves (<a href="/methodology/">how the scores work</a>).';

	var RES_LABELS = { '1080p': '1080p (Full HD)', '1440p': '1440p (QHD)', '4k': '4K (UHD)' };

	// Phase 2 audit fix (Oct 2026): per-resolution engine. Must stay identical
	// to cs_calculate_bottleneck() in inc/bottleneck.php — see that file's
	// header for the full method. In short: the CPU's gaming ceiling
	// (score * BN_CPU_PARITY) barely moves with resolution, while the GPU's
	// throughput shrinks with pixel count (score * BN_GPU_RES_FACTOR[res]).
	// Comparing the two separately at each resolution lets a pair be
	// CPU-bound at 1080p and GPU-bound at 4K.
	// D3 (Oct 2026): graphics preset, game type and a VRAM / memory-bus
	// penalty (same constants as the PHP file), and the % is read two ways:
	// CPU-bound = share of the GPU left idle (the bottleneck, with a severity
	// label); GPU-bound = normal, and the % is the CPU's headroom.
	var BN_CPU_PARITY = 0.85;
	var BN_GPU_RES_FACTOR = { '1080p': 1.0, '1440p': 0.68, '4k': 0.42 };
	var BN_PRESET_FACTOR = { low: 1.7, medium: 1.3, high: 1.0, ultra: 0.8 };
	var BN_GAME_PROFILE = {
		aaa: { cpu: 1.0, gpu: 1.0 },
		esports: { cpu: 2.8, gpu: 3.0 },
		'cpu-heavy': { cpu: 0.7, gpu: 1.0 }
	};
	// The GPU VRAM Calculator's minimums (CS_DEFS.vram "base"; low = medium).
	var BN_VRAM_NEED = {
		'1080p': { low: 6, medium: 6, high: 6, ultra: 8 },
		'1440p': { low: 6, medium: 6, high: 8, ultra: 10 },
		'4k': { low: 8, medium: 8, high: 12, ultra: 14 }
	};
	var BN_NARROW_BUS = { '1080p': 1.0, '1440p': 0.96, '4k': 0.92 };
	var GAME_TYPE_OPTIONS = [
		{ value: 'aaa', label: 'Typical AAA / single-player' },
		{ value: 'esports', label: 'Esports / competitive (Valorant, CS2, Fortnite)' },
		{ value: 'cpu-heavy', label: 'CPU-heavy (strategy, simulation, MMO)' }
	];
	var PRESET_OPTIONS = [
		{ value: 'low', label: 'Low' }, { value: 'medium', label: 'Medium' }, { value: 'high', label: 'High' }, { value: 'ultra', label: 'Ultra / Max' }
	];

	function bnMemoryFactor( gpu, res, preset, game ) {
		var f = 1;
		if ( game !== 'esports' && gpu.vram_gb ) {
			var short = BN_VRAM_NEED[ res ][ preset ] - Number( gpu.vram_gb );
			if ( short > 0 ) {
				f *= Math.max( 0.75, 1 - 0.05 * short );
			}
		}
		if ( gpu.memory_bus && Number( gpu.memory_bus ) <= 128 ) {
			f *= BN_NARROW_BUS[ res ];
		}
		return f;
	}

	function bnSeverity( component, percent ) {
		if ( component === 'balanced' ) {
			return 'balanced';
		}
		if ( component === 'gpu' ) {
			return 'normal';
		}
		return percent >= 30 ? 'severe' : ( percent >= 15 ? 'noticeable' : 'mild' );
	}

	function bottleneckEstimate( cpu, gpu, opts ) {
		opts = opts || {};
		var preset = BN_PRESET_FACTOR[ opts.preset ] ? opts.preset : 'high';
		var game = BN_GAME_PROFILE[ opts.game ] ? opts.game : 'aaa';
		var profile = BN_GAME_PROFILE[ game ];
		var cpuEff = cpu.score * BN_CPU_PARITY * profile.cpu;
		var out = {};
		Object.keys( BN_GPU_RES_FACTOR ).forEach( function ( res ) {
			var gpuEff = gpu.score * BN_GPU_RES_FACTOR[ res ] * BN_PRESET_FACTOR[ preset ] * profile.gpu * bnMemoryFactor( gpu, res, preset, game );
			var stronger = Math.max( cpuEff, gpuEff );
			// Rounded to 4 decimals first, same as the PHP mirror, so both agree
			// on values that land exactly on .5 after float math.
			var share = stronger > 0 ? Number( ( Math.abs( gpuEff - cpuEff ) / stronger * 100 ).toFixed( 4 ) ) : 0;
			var raw = Math.floor( share + 0.5 );
			var percent = Math.min( 60, raw );
			var component = gpuEff > cpuEff ? 'cpu' : 'gpu'; // GPU has spare capacity => CPU is the limit.
			if ( percent < 6 ) {
				component = 'balanced';
			}
			out[ res ] = { percent: percent, component: component, capped: raw > 60, severity: bnSeverity( component, percent ) };
		} );
		return out;
	}

	var BN_SEV_WORD = { mild: 'Mild', noticeable: 'Noticeable', severe: 'Severe' };

	// "23%" or "60%+" when the real value is above the cap (audit 1.4).
	function bnPct( r ) {
		return r.percent + ( r.capped ? '%+' : '%' );
	}

	function bnLabel( r ) {
		if ( r.component === 'balanced' ) {
			return 'Balanced';
		}
		if ( r.component === 'gpu' ) {
			return 'GPU-limited, normal (' + bnPct( r ) + ' CPU headroom)';
		}
		return BN_SEV_WORD[ r.severity ] + ' CPU bottleneck (' + bnPct( r ) + ')';
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
			return ( r.component === 'balanced' ? 'balanced' : ( r.component === 'cpu' ? 'CPU-limited' : 'GPU-limited' ) ) + ' at ' + ( res === '4k' ? '4K' : res );
		} );
		return 'The limiting part shifts with resolution: ' + parts[ 0 ] + ', ' + parts[ 1 ] + ' and ' + parts[ 2 ] + '. Higher resolutions give the GPU more pixels to draw, so load moves onto the graphics card.';
	}

	// Phase 8 (Oct 2026): visual for the bottleneck result, a ring gauge for
	// the chosen resolution plus one bar per resolution. Display only; it
	// reads the same bottleneckEstimate() output as the text and table.
	// D3: a GPU-limited gauge shows the CPU's headroom, labelled as normal.
	var BN_RES_SHORT = { '1080p': '1080p', '1440p': '1440p', '4k': '4K' };
	function bnWord( r, compact ) {
		if ( r.component === 'balanced' ) {
			return 'balanced';
		}
		if ( r.component === 'gpu' ) {
			return compact ? 'GPU-limited' : 'GPU-limited (normal)';
		}
		return bnPct( r ) + ' CPU' + ( compact ? '' : ', ' + r.severity );
	}
	function bnVisual( all, res ) {
		var r = all[ res ];
		var circ = 314.16; // 2 * PI * r(50)
		var offset = ( circ * ( 1 - Math.max( r.percent, 2 ) / 100 ) ).toFixed( 2 );
		var center = r.component === 'gpu' ? '<small>CPU headroom</small><small>GPU-limited</small>' : ( r.component === 'balanced' ? '<small>balanced</small>' : '<small>CPU bottleneck</small><small>' + r.severity + '</small>' );
		var html = '<div class="cs-bn-visual">';
		html += '<div class="cs-gauge cs-lb-' + r.component + '"><svg viewBox="0 0 120 120" aria-hidden="true" focusable="false"><circle class="cs-gauge-track" cx="60" cy="60" r="50"/><circle class="cs-gauge-arc" cx="60" cy="60" r="50" stroke-dasharray="' + circ + '" stroke-dashoffset="' + offset + '"/></svg>';
		html += '<div class="cs-gauge-c"><strong>' + bnPct( r ) + '</strong>' + center + '<small>' + BN_RES_SHORT[ res ] + '</small></div></div>';
		html += '<div class="cs-lb" role="list">';
		Object.keys( all ).forEach( function ( k ) {
			var x = all[ k ];
			var width = Math.max( 4, Math.min( 100, Math.round( x.percent / 60 * 100 ) ) );
			html += '<div class="cs-lb-row cs-lb-' + x.component + ( k === res ? ' is-current' : '' ) + '" role="listitem"><span class="cs-lb-res">' + BN_RES_SHORT[ k ] + '</span><span class="cs-lb-track" aria-hidden="true"><span class="cs-lb-fill" style="width:' + width + '%"></span></span><span class="cs-lb-val">' + bnWord( x ) + '</span></div>';
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
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', other: true, label: 'Your CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', other: true, label: 'Your GPU', icon: 'gpu' },
			{ id: 'resolution', type: 'select', label: 'Resolution', icon: 'resolution', options: [
				{ value: '1080p', label: '1080p (Full HD)' }, { value: '1440p', label: '1440p (QHD)' }, { value: '4k', label: '4K (UHD)' }
			], default: '1080p' },
			// D3 (audit 1.7): game type and settings change how the load splits.
			{ id: 'gametype', type: 'select', label: 'Game type', default: 'aaa', options: GAME_TYPE_OPTIONS, help: 'Esports titles lean on the CPU at high frame rates; strategy and simulation games load the CPU much more per frame.' },
			{ id: 'preset', type: 'select', label: 'Graphics settings', default: 'high', options: PRESET_OPTIONS }
		],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ], gpu = hw.gpu[ v.gpu ];
			var all = bottleneckEstimate( cpu, gpu, { preset: v.preset, game: v.gametype } );
			var r = all[ v.resolution ];
			var at = RES_LABELS[ v.resolution ];
			var verdict;
			if ( r.component === 'balanced' ) {
				verdict = 'These parts are well matched at ' + at + '. Neither is a significant limiting factor.';
			} else if ( r.component === 'cpu' ) {
				verdict = BN_SEV_WORD[ r.severity ] + ' CPU bottleneck at ' + at + ': the ' + cpu.name + ' leaves about ' + bnPct( r ) + ' of the ' + gpu.name + '’s capacity unused (GPU usage ' + ( r.capped ? 'under 40%' : 'around ' + ( 100 - r.percent ) + '%' ) + ').';
			} else {
				verdict = 'The ' + gpu.name + ' sets the frame rate at ' + at + ', which is normal for gaming: the graphics card is fully used and the ' + cpu.name + ' has ' + ( r.capped ? 'more than 60%' : 'about ' + r.percent + '%' ) + ' of spare headroom.' + ( r.percent >= 30 ? ' For higher frame rates here, a faster graphics card is what helps; a faster CPU would not.' : '' );
			}
			var shift = bnShiftNote( all );
			if ( shift ) {
				verdict += ' ' + shift;
			}
			var rows = [
				[ r.component === 'gpu' ? 'CPU headroom' : 'Estimated CPU bottleneck', r.component === 'gpu' ? bnPct( r ) + ' (GPU-limited, normal)' : bnPct( r ) + ( r.component === 'cpu' ? ' (' + BN_SEV_WORD[ r.severity ] + ')' : ' (balanced)' ) ],
				[ 'Likely limiting part', r.component === 'balanced' ? 'Balanced' : ( r.component === 'cpu' ? cpu.name : gpu.name ) ],
				[ '1080p', bnLabel( all[ '1080p' ] ) ],
				[ '1440p', bnLabel( all[ '1440p' ] ) ],
				[ '4K', bnLabel( all[ '4k' ] ) ]
			];
			return bnVisual( all, v.resolution ) + resultBlock( verdict, rows, 'a CPU bottleneck % is the share of the GPU left idle (roughly 100 minus GPU usage): under 6% balanced, 6–14% mild, 15–29% noticeable, 30%+ severe. GPU-limited is normal and shows the CPU’s spare headroom. Ray tracing, upscaling and frame generation aren’t modelled. This is a general estimate, not a per-game benchmark; ' + SCORE_NOTE );
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
			// E4 (FUN-04): shown, read and checked only with "Other CPU".
			{ id: 'cpuTdp', type: 'number', label: wattsLabel, default: 125, min: 15, max: 400, help: wattsHelp, showIf: { field: 'cpu', value: 'custom' } }
		];
	}
	// D5 (Oct 2026, audit 1.19): gaming draw as a share of the max
	// turbo/boost power instead of the rated TDP, which over-stated X3D chips
	// (7800X3D: 120W TDP, about 55-75W in games) and under-stated Intel K
	// chips (13900K: 125W TDP, about 140W+ in games). 55% of max power, 45%
	// for X3D chips (their cache keeps clocks and power lower), lands within
	// public review averages for the listed CPUs. Shared by the PC and CPU
	// Power Consumption calculators.
	var CPU_GAMING_SHARE = 0.55;
	var CPU_GAMING_SHARE_X3D = 0.45;
	function cpuGamingWatts( c ) {
		return ( c.max_power_w || c.tdp_w ) * ( /X3D/.test( c.name ) ? CPU_GAMING_SHARE_X3D : CPU_GAMING_SHARE );
	}
	function resolveCpuPower( v, hw ) {
		var c = v.cpu && v.cpu !== 'custom' && hw.cpu ? hw.cpu[ v.cpu ] : null;
		if ( ! c ) {
			return { name: 'Your CPU', tdp: v.cpuTdp, max: v.cpuTdp, gaming: v.cpuTdp, custom: true };
		}
		return { name: c.name, tdp: c.tdp_w, max: c.max_power_w || c.tdp_w, gaming: cpuGamingWatts( c ), custom: false };
	}

	CS_DEFS.psu = {
		share: true,
		fields: [
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU', icon: 'gpu' }
		].concat( cpuPickerFields( 'Other CPU: max turbo/boost power in watts', 'Intel lists this as "Maximum Turbo Power"; for AMD Ryzen use the PPT figure (about TDP × 1.35).' ) ).concat( [
			{ id: 'ramSticks', type: 'number', label: 'Number of RAM sticks', default: 2, min: 1, max: 8, step: 1, integer: true },
			{ id: 'storageDrives', type: 'number', label: 'Storage drives (SSD/HDD)', default: 2, min: 0, max: 8, step: 1, integer: true },
			{ id: 'fans', type: 'number', label: 'Case/AIO fans', default: 3, min: 0, max: 12, step: 1, integer: true },
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
				[ 'CPU (' + cpu.name + ', max turbo/boost power)', cpu.max + 'W' ],
				// D5 (Oct 2026, audit 1.17): the efficiency grade and the
				// ATX 3.x / 12V-2x6 advice scale with the build.
				[ 'Efficiency rating', psuGrade( recommended ) + ' or better' + ( recommended >= 850 ? ' (at this size it runs cooler and quieter, and saves more power)' : ' (Gold runs a little cooler and quieter)' ) ],
				[ 'PSU standard and GPU cable', psuCableAdvice( gpu ) ]
			];
			return resultBlock( 'Get at least ' + ( /^(8|11|18)/.test( String( recommended ) ) ? 'an' : 'a' ) + ' <strong>' + recommended + 'W</strong> power supply from a reputable brand, <strong>' + psuGrade( recommended ) + '</strong> or better' + ( gpuPlug( gpu ) ? ', ideally <strong>ATX 3.1 with a native 12V-2x6 cable</strong>' : '' ) + '.', rows, 'the load is a tally of rated TDP, the CPU’s max turbo/boost power and fixed allowances for the rest of the system, not a wall-meter reading.' );
		}
	};

	/* ---- 3. PC Power Consumption Calculator ---- */
	var PSU_EFF_LOAD = 0.88;
	var PSU_EFF_IDLE = 0.8;
	CS_DEFS['power-consumption'] = {
		fields: [
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU', icon: 'gpu' }
		].concat( cpuPickerFields( 'Other CPU: rated TDP in watts', 'Used as its gaming draw, a rough stand-in: X3D chips usually draw less in games, unlocked Intel K chips more.' ) ).concat( [
			{ id: 'ramSticks', type: 'number', label: 'Number of RAM sticks', default: 2, min: 1, max: 8, step: 1, integer: true },
			{ id: 'storageDrives', type: 'number', label: 'Storage drives (SSD/HDD)', default: 2, min: 0, max: 8, step: 1, integer: true },
			{ id: 'fans', type: 'number', label: 'Case/AIO fans', default: 3, min: 0, max: 12, step: 1, integer: true },
			{ id: 'hoursPerDay', type: 'number', label: 'Gaming / heavy-load hours per day', default: 3, min: 0, max: 24 },
			{ id: 'idleHours', type: 'number', label: 'Idle / light-use hours per day (desktop, browsing, video)', default: 3, min: 0, max: 24, required: false, blankValue: 0 },
			{ id: 'rate', type: 'number', label: 'Electricity price ($ per kWh)', default: 0.15, min: 0.01, max: 1, step: 0.01, help: 'Check a recent utility bill for your actual rate.' }
		] ),
		compute: function ( v, hw ) {
			var gpu = hw.gpu[ v.gpu ];
			var cpu = resolveCpuPower( v, hw );
			if ( v.hoursPerDay + v.idleHours > 24 ) {
				throw { msg: 'Gaming hours and idle hours together cannot be more than 24 per day.' };
			}
			// Oct 2026: separate idle and load figures, as the page describes.
			// Rest of system = the PSU Calculator's own allowance (5W per RAM
			// stick, 6W per drive, 3W per fan, 50W motherboard and misc.).
			// Load: the CPU's gaming draw (cpuGamingWatts(), D5) and the GPU at
			// its rated board power; the PSU Calculator uses the CPU's full
			// turbo peak because a PSU must carry the peak. Idle: CPU and GPU
			// at ~15% of TDP, the same idle factor as the CPU/GPU Power
			// Consumption calculators, and the rest at ~30% of its allowance.
			// D5 (audit 1.19): the parts' draw is then divided by the PSU's
			// efficiency to get the draw from the wall, which is what the bill
			// counts: ~88% under gaming load (between 80+ Bronze and Gold at
			// 40-60% load) and ~80% at idle, where PSUs are least efficient.
			var rest = ( v.ramSticks * 5 ) + ( v.storageDrives * 6 ) + ( v.fans * 3 ) + 50;
			var loadDc = cpu.gaming + gpu.tdp_w + rest;
			var idleDc = ( cpu.tdp + gpu.tdp_w ) * 0.15 + rest * 0.3;
			var loadW = loadDc / PSU_EFF_LOAD;
			var idleW = idleDc / PSU_EFF_IDLE;
			var dailyKwh = ( loadW * v.hoursPerDay + idleW * v.idleHours ) / 1000;
			var dailyCost = dailyKwh * v.rate;
			var monthlyCost = dailyCost * 30;
			var yearlyCost = dailyCost * 365;
			var rows = [
				[ 'Estimated draw from the wall while gaming', fmt( loadW, 0 ) + 'W' ],
				[ 'Estimated draw from the wall at idle / light use', fmt( idleW, 0 ) + 'W' ],
				[ 'Parts’ own draw while gaming (before PSU loss)', fmt( loadDc, 0 ) + 'W, incl. ' + ( cpu.custom ? 'your CPU' : 'the ' + cpu.name ) + ' at about ' + fmt( cpu.gaming, 0 ) + 'W and the ' + gpu.name + ' at ' + gpu.tdp_w + 'W' ],
				[ 'Daily energy use', fmt( dailyKwh, 2 ) + ' kWh' ],
				[ 'Estimated daily cost', '$' + fmtMoney( dailyCost ) ],
				[ 'Estimated monthly cost (30 days)', '$' + fmtMoney( monthlyCost ) ],
				[ 'Estimated yearly cost (365 days)', '$' + fmtMoney( yearlyCost ) ]
			];
			return resultBlock( 'About <strong>' + fmt( loadW, 0 ) + 'W</strong> from the wall while gaming and <strong>' + fmt( idleW, 0 ) + 'W</strong> at idle — roughly <strong>$' + fmtMoney( monthlyCost ) + '/month</strong> at your hours and rate.', rows, 'wall figures include typical PSU efficiency loss (about 88% efficient while gaming, 80% at idle). Real draw varies by game, settings, frame-rate caps and power-saving options.' );
		}
	};

	/* ---- 4. PC Build Cost Calculator ---- */
	// D2 (Oct 2026, audit 1.14/1.15): the page promised a percentage
	// breakdown and a budget comparison the tool didn't have. Shares are of
	// the PC parts subtotal (the page's worked example: GPU $400 of $1,040 =
	// 38%); peripherals and tax/shipping are added to the total but kept out
	// of the split so they don't skew the part balance. No max caps: with a
	// currency choice, a realistic ceiling in dollars is wrong in rupees.
	var BUILD_PARTS = [
		[ 'cpu', 'CPU' ], [ 'gpu', 'GPU' ], [ 'motherboard', 'Motherboard' ], [ 'ram', 'RAM' ],
		[ 'storage', 'Storage' ], [ 'psu', 'PSU' ], [ 'caseP', 'Case' ], [ 'cooler', 'Cooler' ]
	];
	CS_DEFS['build-cost'] = {
		fields: [
			{ id: 'currency', type: 'select', label: 'Currency', default: 'USD', options: [
				{ value: 'USD', label: 'US dollar ($)' }, { value: 'EUR', label: 'Euro (€)' }, { value: 'GBP', label: 'British pound (£)' },
				{ value: 'CAD', label: 'Canadian dollar (C$)' }, { value: 'AUD', label: 'Australian dollar (A$)' },
				{ value: 'INR', label: 'Indian rupee (₹)' }, { value: 'PKR', label: 'Pakistani rupee (Rs)' }
			], help: 'Only changes the symbol shown; the calculator doesn’t convert prices.' },
			{ id: 'budget', type: 'number', label: 'Your total budget (optional)', min: 0, required: false, blankValue: 0, placeholder: 'e.g. 1200' },
			{ id: 'cpu', type: 'number', label: 'CPU price', default: 0, min: 0, required: false },
			{ id: 'gpu', type: 'number', label: 'GPU price', default: 0, min: 0, required: false },
			{ id: 'motherboard', type: 'number', label: 'Motherboard price', default: 0, min: 0, required: false },
			{ id: 'ram', type: 'number', label: 'RAM price', default: 0, min: 0, required: false },
			{ id: 'storage', type: 'number', label: 'Storage price', default: 0, min: 0, required: false },
			{ id: 'psu', type: 'number', label: 'PSU price', default: 0, min: 0, required: false },
			{ id: 'caseP', type: 'number', label: 'Case price', default: 0, min: 0, required: false },
			{ id: 'cooler', type: 'number', label: 'Cooler price', default: 0, min: 0, required: false },
			{ id: 'extras', type: 'number', label: 'Peripherals / monitor / OS / other', default: 0, min: 0, required: false },
			{ id: 'taxShipping', type: 'number', label: 'Tax and shipping', default: 0, min: 0, required: false }
		],
		compute: function ( v ) {
			var sym = { USD: '$', EUR: '€', GBP: '£', CAD: 'C$', AUD: 'A$', INR: '₹', PKR: 'Rs ' }[ v.currency ] || '$';
			var money = function ( n ) { return sym + fmtMoney( n ); };
			var parts = BUILD_PARTS.reduce( function ( sum, p ) { return sum + v[ p[ 0 ] ]; }, 0 );
			var total = parts + v.extras + v.taxShipping;
			if ( total <= 0 ) {
				throw { msg: 'Enter the price of at least one part to total your build.' };
			}
			var pct = function ( n ) { return parts > 0 ? n / parts * 100 : 0; };
			var rows = BUILD_PARTS.map( function ( p ) {
				var n = v[ p[ 0 ] ];
				return [ p[ 1 ], money( n ) + ( parts > 0 ? ' (' + fmt( pct( n ), 0 ) + '%)' : '' ) ];
			} );
			rows.push( [ 'PC parts subtotal', money( parts ) + ' (100%)' ] );
			if ( v.extras ) {
				rows.push( [ 'Peripherals / other (not in the % split)', money( v.extras ) ] );
			}
			if ( v.taxShipping ) {
				rows.push( [ 'Tax and shipping (not in the % split)', money( v.taxShipping ) ] );
			}
			rows.push( [ 'Total build cost', '<strong>' + money( total ) + '</strong>' ] );
			if ( parts > 0 ) {
				// U1 (U-11): gaming build guides put the GPU at about 35-50% of
				// the parts budget (was 25-33%, which called a normal 5600 +
				// RTX 4070 build GPU-heavy); CPU + motherboard + RAM a comparable
				// share. Mirror: cs_aeo_reference_data( 'build-cost' ).
				var gpuShare = Math.round( pct( v.gpu ) );
				var guide = gpuShare < 35 ? 'below' : gpuShare > 50 ? 'above' : 'within';
				rows.push( [ 'GPU share vs the typical 35–50% for a gaming build', fmt( gpuShare, 0 ) + '%, ' + guide + ' that range' + ( guide === 'within' ? '' : ' (fine if intentional, e.g. a work PC or a high-refresh esports build)' ) ] );
				rows.push( [ 'CPU + motherboard + RAM share', money( v.cpu + v.motherboard + v.ram ) + ' (' + fmt( pct( v.cpu + v.motherboard + v.ram ), 0 ) + '%)' ] );
			}
			var headline = 'Estimated total build cost: <strong>' + money( total ) + '</strong>';
			if ( v.budget > 0 ) {
				var left = v.budget - total;
				rows.push( [ 'Your budget', money( v.budget ) ] );
				rows.push( left >= 0 ? [ 'Left in your budget', money( left ) ] : [ 'Over your budget by', money( -left ) ] );
				headline += left >= 0 ? ', leaving <strong>' + money( left ) + '</strong> of your ' + money( v.budget ) + ' budget.' : ', <strong>' + money( -left ) + ' over</strong> your ' + money( v.budget ) + ' budget.';
			}
			return resultBlock( headline, rows, 'the total only adds up the prices you enter (no live prices), and the 35–50% GPU range is a general guide for gaming builds, not a rule.' );
		}
	};

	/* ---- 5. FPS Calculator ---- */

	// Phase 2 (Oct 2026): uses the bottleneck engine's own resolution
	// factors and CPU parity, so at "high" settings the FPS calculator's
	// limiting part always matches the bottleneck calculator's. The CPU
	// ceiling no longer rises with resolution (it used to be 2.4x / 3.0x /
	// 4.2x the CPU score, which let the GPU be the limit almost
	// everywhere): a CPU prepares the same frames per second whatever the
	// resolution. FPS_PER_POINT (2.5 FPS per GPU score point at 1080p
	// high, was 1.8) re-anchors the scale to published 1080p averages,
	// e.g. an RTX 4070 at roughly 135-140 FPS when nothing else limits it.
	// D3 (Oct 2026, audit 1.9): same preset, game-type and VRAM /
	// memory-bus factors as the bottleneck engine, so the limiting part
	// matches it at every setting. Presets now span about 2.1x (low vs
	// ultra), and the esports profile lifts the ceiling from about 200 FPS
	// (9800X3D, AAA) to several hundred.
	// D10: shared by the FPS Calculator and Can I Run It. PHP mirror:
	// cs_tl_fps() in inc/tier-list.php (keep the two in sync).
	var FPS_PER_POINT = 2.5;
	function fpsCeilings( cpu, gpu, res, preset, gameType, gpuLoad ) {
		var profile = BN_GAME_PROFILE[ gameType ] || BN_GAME_PROFILE.aaa;
		return {
			gpu: gpu.score * FPS_PER_POINT * BN_GPU_RES_FACTOR[ res ] * BN_PRESET_FACTOR[ preset ] * profile.gpu * bnMemoryFactor( gpu, res, preset, gameType ) * ( gpuLoad || 1 ),
			cpu: cpu.score * BN_CPU_PARITY * FPS_PER_POINT * profile.cpu
		};
	}

	// U1 (Oct 2026, U-01): with only the game-type profile, every AAA game
	// got the same FPS (a 5600 + RTX 3060 showed "Ultra ~68" for Cyberpunk,
	// Starfield and RDR2 alike). A picked game now also scales the GPU's
	// ceiling by how heavy its publisher-recommended GPU is (data/
	// games-data.php 'req', on our score scale) next to the median for its
	// type: a game that asks for a faster card than its peers runs slower.
	// Half weight (square root), because publishers aim their recommended
	// tier at different settings and years, and kept within 0.75-1.3 of the
	// type's figure. GAME_LOAD_REF = median recommended GPU score per type
	// in the games list (AAA 30, esports 11, CPU-heavy 32). Only per-game
	// tools use it: the bottleneck engine and part pages stay per type.
	// PHP mirror: cs_game_gpu_load() in inc/d10-pages.php.
	var GAME_LOAD_REF = { aaa: 30, esports: 11, 'cpu-heavy': 32 };
	function gameGpuLoad( g, type ) {
		var rec = g && g.req && g.req.rec ? Number( g.req.rec.gpu ) : 0;
		if ( ! rec || ! GAME_LOAD_REF[ type ] ) {
			return 1;
		}
		return clamp( Math.sqrt( GAME_LOAD_REF[ type ] / rec ), 0.75, 1.3 );
	}

	// D10 (audit 5.7): upscaling (DLSS / FSR / XeSS) renders fewer pixels,
	// so it lifts only the GPU's ceiling; a CPU-limited PC gains little.
	// Typical uplifts from published DLSS / FSR tests, bigger at higher
	// output resolutions where the GPU does more of the work. Frame
	// generation inserts generated frames between rendered ones: shown FPS
	// rises, but responsiveness follows the rendered (base) frame rate.
	// FG_COST: the generated-frame pass takes about 10% of the base rate.
	var UPSCALE_GAIN = {
		quality: { '1080p': 1.25, '1440p': 1.35, '4k': 1.4 },
		balanced: { '1080p': 1.35, '1440p': 1.5, '4k': 1.6 },
		performance: { '1080p': 1.5, '1440p': 1.7, '4k': 1.85 }
	};
	var FG_COST = 0.9;
	var UPSCALE_OPTIONS = [
		{ value: 'off', label: 'Off (native resolution)' },
		{ value: 'quality', label: 'Quality' },
		{ value: 'balanced', label: 'Balanced' },
		{ value: 'performance', label: 'Performance' }
	];
	var FRAMEGEN_OPTIONS = [
		{ value: 'off', label: 'Off' },
		{ value: '2', label: '2x (DLSS 3 / FSR 3 / XeSS 2 frame generation)' },
		{ value: '3', label: '3x (RTX 50 Multi Frame Generation)' },
		{ value: '4', label: '4x (RTX 50 Multi Frame Generation)' }
	];

	CS_DEFS.fps = {
		share: true,
		comboLink: true,
		fields: [
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', other: true, label: 'CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', other: true, label: 'GPU', icon: 'gpu' },
			{ id: 'resolution', type: 'select', label: 'Resolution', icon: 'resolution', default: '1080p', options: [
				{ value: '1080p', label: '1080p (Full HD)' }, { value: '1440p', label: '1440p (QHD)' }, { value: '4k', label: '4K (UHD)' }
			] },
			{ id: 'preset', type: 'select', label: 'Graphics settings', default: 'high', options: PRESET_OPTIONS },
			{ id: 'gametype', type: 'select', label: 'Game type', default: 'aaa', options: GAME_TYPE_OPTIONS, help: 'Esports titles run at several hundred FPS on fast hardware; strategy and simulation games load the CPU much more.' },
			{ id: 'game', type: 'select', label: 'Game (optional)', default: '', options: gameOptions(), help: 'Adds that game\'s officially published minimum/recommended specs next to the estimate, and uses its game type.' },
			{ id: 'upscaling', type: 'select', label: 'Upscaling (DLSS / FSR / XeSS)', default: 'off', options: UPSCALE_OPTIONS, help: 'Renders at a lower resolution and upscales. Only works in games that support it.' },
			{ id: 'framegen', type: 'select', label: 'Frame generation', default: 'off', options: FRAMEGEN_OPTIONS, help: 'Adds generated frames: smoother motion, but input feel follows the base frame rate.' }
		],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ], gpu = hw.gpu[ v.gpu ];
			// A picked game with a known type (data/games-data.php 'profile')
			// sets the game type.
			var game = v.game && GAMES[ v.game ] ? GAMES[ v.game ] : null;
			var gameType = game && BN_GAME_PROFILE[ game.profile ] ? game.profile : ( BN_GAME_PROFILE[ v.gametype ] ? v.gametype : 'aaa' );
			var preset = BN_PRESET_FACTOR[ v.preset ] ? v.preset : 'high';
			// U1 (U-01): the picked game's own load (gameGpuLoad()).
			var load = game ? gameGpuLoad( game, gameType ) : 1;
			var c = fpsCeilings( cpu, gpu, v.resolution, preset, gameType, load );
			var upscale = UPSCALE_GAIN[ v.upscaling ] ? v.upscaling : 'off';
			// E4 (FUN-06): a picked game without its own upscaling or frame
			// generation option (data/games-data.php) gets neither, with a note.
			var gameNotes = [];
			// U1 (U-01): a PC below the game's published minimum is told so
			// first, before any FPS figure.
			if ( game && game.req ) {
				var lvl = cirLevel( cpu, gpu, Infinity, game );
				if ( lvl.level === 'below' ) {
					gameNotes.push( 'Your ' + lvl.short.join( ' and ' ) + ' is below ' + game.name + '’s published minimum, so the game may not start or may run under 30 FPS even at low settings. Treat the figure below as a best case.' );
				}
			}
			if ( game && game.fps_varies ) {
				gameNotes.push( game.name + '’s frame rate depends mostly on its render distance, mods and shader packs, so this is only the general estimate for its game type; vanilla at the default render distance often runs much higher.' );
			}
			if ( game && game.upscaling === false && upscale !== 'off' ) {
				gameNotes.push( game.name + ' has no built-in upscaling (DLSS / FSR / XeSS), so this estimate is at native resolution.' );
				upscale = 'off';
			}
			var gpuFps = c.gpu * ( upscale !== 'off' ? UPSCALE_GAIN[ upscale ][ v.resolution ] : 1 );
			var cpuCap = c.cpu;
			var estimate = Math.min( gpuFps, cpuCap );
			// The FPS calculator page says the range is wider when CPU and GPU
			// both matter and narrower when one part clearly dominates; it used to
			// be a flat ±18%. Now ±22% when the two ceilings are within 15% of
			// each other, ±15% otherwise (still "roughly ±18%" as /methodology/ says).
			var closeness = Math.min( gpuFps, cpuCap ) / Math.max( gpuFps, cpuCap );
			var spread = closeness >= 0.85 ? 0.22 : 0.15;
			// Frame generation: 3x/4x is RTX 50 only; other cards top out at 2x.
			var fg = v.framegen === '2' || v.framegen === '3' || v.framegen === '4' ? Number( v.framegen ) : 1;
			if ( game && game.framegen === false && fg > 1 ) {
				gameNotes.push( game.name + ' has no frame generation option, so none is added.' );
				fg = 1;
			}
			var fgNote = '';
			if ( fg > 2 && gpu.fg !== 'mfg' ) {
				fgNote = gpu.name + ' supports up to 2x frame generation, so 2x is used.';
				fg = 2;
			}
			var shown = fg > 1 ? estimate * FG_COST * fg : estimate;
			var low = Math.round( shown * ( 1 - spread ) );
			var high = Math.round( shown * ( 1 + spread ) );
			// U1 (U-03): a game's built-in frame-rate lock caps everything shown.
			var cap = game && game.fps_cap ? Number( game.fps_cap ) : 0;
			var capped = cap && low >= cap ? 'all' : ( cap && high > cap ? 'part' : '' );
			if ( cap ) {
				shown = Math.min( shown, cap );
				low = Math.min( low, cap );
				high = Math.min( high, cap );
				if ( capped ) {
					gameNotes.push( game.name + ' is locked to ' + cap + ' FPS on PC, so the estimate stops there' + ( capped === 'all' ? '; this PC would otherwise run it faster.' : '.' ) );
				}
			}
			// Same "under 6% = balanced" rule as the bottleneck calculator.
			var limiter = Math.round( ( 1 - closeness ) * 100 ) < 6 ? 'Balanced (CPU and GPU about even)' : ( gpuFps < cpuCap ? gpu.name + ' (GPU-bound)' : cpu.name + ' (CPU-bound)' );
			var rows = [
				[ 'Estimated FPS range', capped === 'all' ? cap + ' FPS (the game’s cap)' : low + ' – ' + high + ' FPS' ],
				[ 'Midpoint estimate', Math.round( shown ) + ' FPS' ],
				[ 'Likely limiting part', capped === 'all' ? 'The game’s ' + cap + ' FPS cap' : limiter ]
			];
			// Game selection (Sep 2026 audit): shows the publisher's own
			// minimum/recommended specs from data/games-data.php beside the
			// estimate. The FPS number follows the game's broad type (D3) and,
			// since U1, how heavy its published recommended GPU is
			// (gameGpuLoad()); no game is benchmarked by us.
			rows.push( [ 'Game type used', escapeHtml( GAME_TYPE_OPTIONS.filter( function ( o ) { return o.value === gameType; } )[ 0 ].label ) + ( game ? ' (set by the game)' : '' ) ] );
			if ( game && Math.round( ( load - 1 ) * 100 ) !== 0 ) {
				rows.push( [ 'Adjusted for this game', 'graphics load about ' + Math.abs( Math.round( ( 1 / load - 1 ) * 100 ) ) + '% ' + ( load < 1 ? 'heavier' : 'lighter' ) + ' than a typical game of its type, going by its published recommended graphics card' ] );
			}
			var tech = gpu.upscaler ? gpu.upscaler : 'FSR';
			if ( upscale !== 'off' ) {
				var native = Math.min( c.gpu, cpuCap );
				rows.push( [ 'Upscaling', escapeHtml( tech + ' ' + upscale.charAt( 0 ).toUpperCase() + upscale.slice( 1 ) ) + ': about ' + Math.round( native ) + ' FPS at native resolution' + ( gpuFps >= cpuCap ? ', and the CPU limits the gain' : '' ) ] );
			}
			if ( fg > 1 ) {
				rows.push( [ 'Frame generation', fg + 'x: about ' + Math.round( estimate * FG_COST ) + ' rendered FPS, ' + Math.round( shown ) + ' shown. Input feel follows the rendered rate' + ( estimate * FG_COST < 50 ? ', which is low enough to feel sluggish (makers suggest about 60 before turning it on)' : '' ) + '.' ] );
				if ( fgNote ) {
					rows.push( [ 'Note', escapeHtml( fgNote ) ] );
				}
			}
			if ( game ) {
				rows.push( [ game.name + ' — publisher minimum', escapeHtml( game.min.cpu + ' · ' + game.min.gpu + ' · ' + game.min.ram + 'GB RAM' ) ] );
				rows.push( [ game.name + ' — publisher recommended', escapeHtml( game.rec.cpu + ' · ' + game.rec.gpu + ' · ' + game.rec.ram + 'GB RAM' ) ] );
				rows.push( [ 'Full requirements', '<a href="' + escapeHtml( game.url ) + '">' + escapeHtml( game.name ) + ' system requirements</a>' ] );
			}
			var noteHtml = gameNotes.length ? '<div class="cs-warnings">' + gameNotes.map( function ( n ) {
				return '<p class="cs-warning">⚠ ' + escapeHtml( n ) + '</p>';
			} ).join( '' ) + '</div>' : '';
			var extras = upscale !== 'off' || fg > 1;
			return noteHtml + resultBlock( 'Estimated average: <strong>' + Math.round( shown ) + ' FPS</strong> at ' + RES_LABELS[ v.resolution ] + ', ' + v.preset + ' settings' + ( extras ? ' with ' + [ upscale !== 'off' ? escapeHtml( tech ) + ' ' + upscale : '', fg > 1 ? fg + 'x frame generation' : '' ].filter( Boolean ).join( ' and ' ) : '' ) + ( game ? ' in ' + escapeHtml( game.name ) + ' — an estimate from its game type and published requirements, not a benchmark of the game' : '' ) + '.', rows, 'actual FPS varies significantly by game engine, optimization, RAM speed and drivers. Ray tracing isn’t modelled, and upscaling and frame generation gains are typical averages that need game support (<a href="/dlss-fsr-frame-generation-explained/">how they work</a>); ' + SCORE_NOTE );
		}
	};

	/* ---- 5b. Can I Run It (D10, audit 5.2) ---- */
	// Every game in data/games-data.php against the visitor's own PC: the
	// highest preset that reaches the target frame rate (same formula as
	// the FPS Calculator, by game type), plus RAM against the publisher's
	// minimum and recommended. Server-side mirror of the example table:
	// cs_cir_best() in inc/d10-pages.php.
	// U1: plus the game's own load (gameGpuLoad()) and frame-rate cap.
	var CIR_PRESETS = [ 'ultra', 'high', 'medium', 'low' ];
	function cirBest( cpu, gpu, res, target, gameType, g ) {
		var fps = 0;
		var load = gameGpuLoad( g, gameType );
		var cap = g && g.fps_cap ? Number( g.fps_cap ) : 0;
		for ( var i = 0; i < CIR_PRESETS.length; i++ ) {
			var c = fpsCeilings( cpu, gpu, res, CIR_PRESETS[ i ], gameType, load );
			fps = Math.min( c.gpu, c.cpu );
			if ( cap ) {
				fps = Math.min( fps, cap );
			}
			if ( fps >= target ) {
				return { preset: CIR_PRESETS[ i ], fps: fps, capped: !! cap && fps >= cap };
			}
		}
		return { preset: '', fps: fps, capped: false };
	}

	// Publisher tier the PC meets: 'rec', 'min' or 'below', and which parts
	// fall short of the next tier up. Mirror: cs_cir_level() in PHP.
	function cirLevel( cpu, gpu, ram, g ) {
		var req = g.req || { min: { cpu: 0, gpu: 0 }, rec: { cpu: 0, gpu: 0 } };
		var below = [], under = [];
		if ( cpu.score < req.min.cpu ) { below.push( 'CPU' ); } else if ( cpu.score < req.rec.cpu ) { under.push( 'CPU' ); }
		if ( gpu.score < req.min.gpu ) { below.push( 'GPU' ); } else if ( gpu.score < req.rec.gpu ) { under.push( 'GPU' ); }
		if ( ram < g.min.ram ) { below.push( 'RAM' ); } else if ( ram < g.rec.ram ) { under.push( 'RAM' ); }
		if ( below.length ) {
			return { level: 'below', short: below };
		}
		return { level: under.length ? 'min' : 'rec', short: under };
	}

	CS_DEFS.canirun = {
		share: true, // E4 (UX-03)
		fields: [
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', other: true, label: 'Your CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', other: true, label: 'Your GPU', icon: 'gpu' },
			{ id: 'ram', type: 'select', label: 'Your RAM', icon: 'ram', default: '16', options: [
				{ value: '8', label: '8GB' }, { value: '12', label: '12GB' }, { value: '16', label: '16GB' }, { value: '24', label: '24GB' }, { value: '32', label: '32GB' }, { value: '64', label: '64GB or more' }
			] },
			{ id: 'resolution', type: 'select', label: 'Resolution', icon: 'resolution', default: '1080p', options: [
				{ value: '1080p', label: '1080p (Full HD)' }, { value: '1440p', label: '1440p (QHD)' }, { value: '4k', label: '4K (UHD)' }
			] },
			{ id: 'target', type: 'select', label: 'Smooth enough for you', default: '60', options: [
				{ value: '30', label: '30 FPS (console-like)' }, { value: '60', label: '60 FPS (smooth)' }, { value: '120', label: '120 FPS (high refresh)' }, { value: '144', label: '144 FPS (competitive)' }
			] }
		],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ], gpu = hw.gpu[ v.gpu ];
			var ram = Number( v.ram ) || 16;
			var target = Number( v.target ) || 60;
			var keys = Object.keys( GAMES );
			if ( ! keys.length ) {
				throw { msg: 'The games list didn’t load. Please refresh the page.' };
			}
			var label = { ultra: 'Ultra', high: 'High', medium: 'Medium', low: 'Low' };
			var count = { rec: 0, min: 0, below: 0 }, playable = 0, estimated = 0;
			var belowNames = [];
			var rows = keys.map( function ( key ) {
				var g = GAMES[ key ];
				var type = BN_GAME_PROFILE[ g.profile ] ? g.profile : 'aaa';
				var lvl = cirLevel( cpu, gpu, ram, g );
				count[ lvl.level ]++;
				var req = lvl.level === 'rec' ? '<strong>Meets recommended</strong>' : ( lvl.level === 'min' ? 'Meets minimum' + ( lvl.short.length ? ' (' + lvl.short.join( ', ' ) + ' below recommended)' : '' ) : '<strong>Below minimum</strong> (' + lvl.short.join( ', ' ) + ')' );
				var cell;
				// U1 (U-01): a game the PC is below the publisher minimum for
				// gets no FPS figure and is not counted as reaching the target.
				if ( lvl.level === 'below' ) {
					belowNames.push( g.name );
					cell = 'Not counted: your ' + lvl.short.join( ' and ' ) + ' is below the publisher’s minimum, so it may not start or may run under 30 FPS even at Low';
					return [ '<a href="' + escapeHtml( g.url ) + '">' + escapeHtml( g.name ) + '</a>', req, cell ];
				}
				if ( g.fps_varies ) {
					cell = 'No estimate: its FPS depends mostly on render distance, mods and shader packs';
					return [ '<a href="' + escapeHtml( g.url ) + '">' + escapeHtml( g.name ) + '</a>', req, cell ];
				}
				estimated++;
				var cap = g.fps_cap ? Number( g.fps_cap ) : 0;
				var best = cirBest( cpu, gpu, v.resolution, target, type, g );
				if ( best.preset ) {
					playable++;
					cell = label[ best.preset ] + ' · ~' + Math.round( best.fps ) + ' FPS' + ( best.capped ? ' <small>(the game’s ' + cap + ' FPS cap)</small>' : '' );
					// The estimate is for the game's type and published specs; a
					// publisher asking for a faster part than yours marks a game that
					// can run slower.
					if ( lvl.short.indexOf( 'GPU' ) !== -1 || lvl.short.indexOf( 'CPU' ) !== -1 ) {
						cell += ' <small>(this game asks for more than your ' + lvl.short.filter( function ( p ) { return p !== 'RAM'; } ).join( ' and ' ) + ', so expect lower)</small>';
					}
				} else if ( cap && target > cap ) {
					// U1 (U-03): a target above the game's own frame-rate lock.
					var atCap = cirBest( cpu, gpu, v.resolution, cap, type, g );
					cell = 'Locked at ' + cap + ' FPS by the game' + ( atCap.preset ? ' <small>(' + label[ atCap.preset ] + ' reaches it)</small>' : '' );
				} else {
					cell = 'Under ' + target + ' FPS at Low (~' + Math.round( best.fps ) + ')';
				}
				return [ '<a href="' + escapeHtml( g.url ) + '">' + escapeHtml( g.name ) + '</a>', req, cell ];
			} );
			var html = '<div class="cs-result-block">';
			var headline = '';
			if ( count.below ) {
				headline += 'Your PC is <strong>below the minimum specs of ' + count.below + ' of ' + keys.length + ' games</strong> (' + escapeHtml( belowNames.join( ', ' ) ) + '). ';
			}
			headline += ( count.below ? 'It' : 'Your PC' ) + ' meets the ' + ( count.below ? '' : '<strong>' ) + 'recommended specs of ' + count.rec + ' of ' + keys.length + ' games' + ( count.below ? '' : '</strong>' ) + ( count.min ? ' and the minimum of ' + count.min + ' more' : '' ) + '. ';
			headline += ( count.below ? 'Of the rest, ' : '' ) + playable + ' of ' + estimated + ' games we estimate reach ' + target + '+ FPS at ' + RES_LABELS[ v.resolution ] + ' at some preset.';
			html += '<p class="cs-result-headline">' + headline + '</p>';
			html += '<div class="cs-table-wrap"><table class="cs-result-table cs-cir-table"><thead><tr><th scope="col">Game</th><th scope="col">Publisher specs</th><th scope="col">Highest settings at ' + target + '+ FPS</th></tr></thead><tbody>';
			rows.forEach( function ( r ) {
				html += '<tr><th scope="row">' + r[ 0 ] + '</th><td>' + r[ 1 ] + '</td><td>' + r[ 2 ] + '</td></tr>';
			} );
			html += '</tbody></table></div>';
			html += '<p class="cs-disclaimer"><strong>Estimate only:</strong> the publisher’s minimum and recommended parts are placed on our score scale (approximate where a part isn’t in our database). FPS uses the FPS Calculator’s formula for the game’s type (typical AAA, esports or CPU-heavy), adjusted for how demanding its published recommended graphics card is and capped where the game locks its frame rate. It is not a benchmark of each game, and games you’re below the minimum for aren’t counted. Ray tracing is off; ' + ( gpu.upscaler ? escapeHtml( gpu.upscaler ) + ' upscaling' : 'upscaling' ) + ' can add more; ' + SCORE_NOTE + '</p>';
			return html + '</div>';
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
				// U1 (U-53): an 8GB kit is one 8GB stick or 2 × 4GB, and 4GB DDR5
				// sticks aren't made, so 8GB is shown as one stick with 16GB
				// (2 × 8GB) as the dual-channel step up.
				[ 'Typical setup', suggestedKit === 8 ? '1 × 8 GB stick, or 16 GB (2 × 8 GB) for dual channel and headroom' : ( suggestedKit / 2 ) + ' GB × 2 sticks (dual channel)' ]
			];
			return resultBlock( 'We recommend at least <strong>' + suggestedKit + 'GB</strong> of RAM for this use case.', rows, 'a general guideline for each use case, not measured memory use. Some games, mods and large projects need more.' );
		}
	};

	/* ---- 7. PC Upgrade Calculator ---- */
	// U1 (U-12): the three slowest current cards (released 2024 or later)
	// that reach a GPU score target, slowest first.
	function upgradeGpuPicks( hw, target ) {
		return Object.keys( hw.gpu ).filter( function ( k ) {
			return hw.gpu[ k ].score >= target && Number( hw.gpu[ k ].release_year ) >= 2024;
		} ).sort( function ( a, b ) { return hw.gpu[ a ].score - hw.gpu[ b ].score; } ).slice( 0, 3 );
	}
	// The fastest gaming CPU on the same socket, when it beats this one
	// (cs_bn_socket_upgrade() on the combo pages).
	function upgradeCpuPick( hw, slug, cpu ) {
		var best = '';
		Object.keys( hw.cpu ).forEach( function ( k ) {
			if ( k !== slug && hw.cpu[ k ].socket === cpu.socket && hw.cpu[ k ].score > cpu.score && ( ! best || hw.cpu[ k ].score > hw.cpu[ best ].score ) ) {
				best = k;
			}
		} );
		return best;
	}
	CS_DEFS.upgrade = {
		share: true, // E4 (UX-03)
		fields: [
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', other: true, label: 'Current CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', other: true, label: 'Current GPU', icon: 'gpu' },
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
			// GPU targets = the lowest score among cards this site's own tier
			// labels (data/hardware-data.php 'tier') call suitable for that
			// resolution, so the advisor never calls a "1440p" card too weak for
			// 1440p. inc/bottleneck-content.php cs_bn_upgrade_targets() mirrors
			// these.
			// D3 (Oct 2026, audit 1.13): for gaming targets the CPU decision comes
			// from the bottleneck engine at that resolution (typical AAA game,
			// high settings), so this tool can no longer flag a CPU that the
			// Bottleneck Calculator says is not the limit. The old fixed CPU
			// targets (55 / 53 / 50) are gone. Productivity keeps a CPU score
			// target (59), since the engine only models games.
			// Items are sorted by severity instead of a fixed storage, RAM, GPU,
			// CPU order. Severity, 0-100: GPU = how far below the target its
			// score is (%); CPU = the engine's CPU bottleneck %; HDD = 30 (the
			// biggest everyday speed-up); RAM = 40 at 8GB or less, 20 under 16GB.
			var gaming = v.target !== 'productivity';
			var targetGpuScore = { '1080p': 33, '1440p': 50, '4k': 70, productivity: 55 }[ v.target ];
			var resParam = gaming ? '&resolution=' + encodeURIComponent( v.target ) : '';
			var items = [];
			var notes = [];
			if ( v.storageType === 'hdd' ) {
				items.push( { sev: 30, html: escapeHtml( 'Storage — moving to an NVMe SSD is the single biggest everyday speed upgrade you can make.' ) } );
			}
			if ( v.ram < 16 ) {
				items.push( { sev: v.ram <= 8 ? 40 : 20, html: escapeHtml( 'RAM — ' + v.ram + 'GB is tight for modern games and multitasking; 16GB is the current sensible minimum.' ) } );
			}
			if ( gpu.score < targetGpuScore ) {
				// U1 (U-12): name the level and the slowest current cards that
				// reach it, with a link to try one in the FPS Calculator.
				var picks = upgradeGpuPicks( hw, targetGpuScore );
				var gpuHtml = escapeHtml( 'GPU — the ' + gpu.name + ' is below the performance level typically needed for ' + ( gaming ? RES_LABELS[ v.target ] + ' gaming' : 'GPU-accelerated creative work' ) + ' (a gaming score of ' + targetGpuScore + ' or more' + ( picks.length ? ', for example the ' + picks.map( function ( k ) { return hw.gpu[ k ].name; } ).join( ', ' ) + ' or faster' : '' ) + ').' );
				if ( picks.length ) {
					gpuHtml += ' <a href="/fps-calculator/?cpu=' + encodeURIComponent( v.cpu ) + '&amp;gpu=' + encodeURIComponent( picks[ 0 ] ) + resParam + '">' + escapeHtml( 'Try the ' + hw.gpu[ picks[ 0 ] ].name + ' with your CPU in the FPS Calculator' ) + '</a>, or see the <a href="/gpu-tier-list/">GPU tier list</a>.';
				}
				items.push( { sev: Math.round( ( targetGpuScore - gpu.score ) / targetGpuScore * 100 ), html: gpuHtml } );
			}
			var up = upgradeCpuPick( hw, v.cpu, cpu );
			var cpuNext = up ? ' On the same ' + cpu.socket + ' motherboard, the fastest gaming option in our list is the ' + hw.cpu[ up ].name + ' (check the board’s CPU support list for a BIOS update).' : ' No faster ' + cpu.socket + ' CPU is in our list, so a bigger step means a new motherboard.';
			var cpuLink = up ? ' <a href="/pc-bottleneck-calculator/?cpu=' + encodeURIComponent( up ) + '&amp;gpu=' + encodeURIComponent( v.gpu ) + resParam + '">' + escapeHtml( 'Check the ' + hw.cpu[ up ].name + ' with your GPU' ) + '</a>.' : ' See the <a href="/cpu-tier-list/">CPU tier list</a>.';
			if ( gaming ) {
				var r = bottleneckEstimate( cpu, gpu )[ v.target ];
				var topCpu = cpu.score >= hwScoreMax( hw.cpu ) * 0.97;
				if ( r.component === 'cpu' && r.percent >= 15 && topCpu ) {
					// U1 (U-04): same rule as cs_bn_is_top_cpu(): nothing faster exists.
					notes.push( 'The ' + gpu.name + ' outruns even the ' + cpu.name + ' at ' + RES_LABELS[ v.target ] + ' (CPU bottleneck of about ' + bnPct( r ) + '), but no faster gaming CPU exists on any socket. That is normal for a card this fast; a higher resolution or settings puts its extra speed to use.' );
				} else if ( r.component === 'cpu' && r.percent >= 15 ) {
					items.push( { sev: r.percent, html: escapeHtml( 'CPU — the ' + cpu.name + ' holds the ' + gpu.name + ' back at ' + RES_LABELS[ v.target ] + ': ' + BN_SEV_WORD[ r.severity ].toLowerCase() + ' CPU bottleneck of about ' + bnPct( r ) + ' in our Bottleneck Calculator.' + cpuNext ) + cpuLink } );
				} else if ( r.component === 'cpu' ) {
					notes.push( 'The ' + cpu.name + ' shows only a mild CPU bottleneck (' + bnPct( r ) + ') with this card at ' + RES_LABELS[ v.target ] + ', so a CPU upgrade isn’t a priority.' );
				} else if ( r.component === 'gpu' ) {
					// U1 (U-12): no "faster GPU would add FPS" right after "no
					// upgrade is necessary"; worded as a later option instead.
					notes.push( 'Your CPU isn’t the limit at ' + RES_LABELS[ v.target ] + ': the ' + gpu.name + ' sets the frame rate (normal) and the CPU has about ' + bnPct( r ) + ' headroom. ' + ( gpu.score < targetGpuScore ? 'So the GPU upgrade above is what adds FPS, not a new CPU.' : 'If you want more FPS later, a faster graphics card is the upgrade that would add it.' ) );
				} else {
					notes.push( 'The ' + cpu.name + ' and ' + gpu.name + ' are balanced at ' + RES_LABELS[ v.target ] + '.' );
				}
			} else if ( cpu.score < 59 ) {
				items.push( { sev: Math.round( ( 59 - cpu.score ) / 59 * 100 ), html: escapeHtml( 'CPU — the ' + cpu.name + ' is below the level typically wanted for productivity and creative work.' + cpuNext ) + ( up ? '' : cpuLink ) } );
			}
			items.sort( function ( a, b ) { return b.sev - a.sev; } );
			var html = '<div class="cs-result-block"><p class="cs-result-headline">' + ( items.length ? 'Suggested upgrade priority (biggest gain first):' : 'Your current CPU, GPU, RAM and storage already look well suited to this target — no upgrade is strictly necessary yet.' ) + '</p>';
			if ( items.length ) {
				html += '<ol class="cs-priority-list">';
				items.forEach( function ( it ) { html += '<li>' + it.html + '</li>'; } );
				html += '</ol>';
			}
			notes.forEach( function ( n ) { html += '<p>' + escapeHtml( n ) + '</p>'; } );
			html += '<p class="cs-disclaimer"><strong>Estimate only:</strong> based on typical requirements for your stated goal and, for gaming, the same engine as the PC Bottleneck Calculator (typical AAA game, high settings), not your exact game library.</p></div>';
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
	/*
	 * Oct 2026: CPU and GPU pickers from data/hardware-data.php, so the
	 * checker reads the CPU's socket and checks the PSU the same way the PSU
	 * Calculator does (CPU max turbo/boost power + GPU board power + the PSU
	 * Calculator's default RAM/drives/fans/motherboard allowance, +30%,
	 * rounded up to 50W, never below the GPU maker's own recommended PSU).
	 * "Other" keeps the manual socket / power boxes for parts not listed.
	 */
	function pickerWithOther( source, otherLabel ) {
		var opts = hardwareOptions( source ).slice();
		opts.push( { value: 'custom', label: otherLabel } );
		return opts;
	}

	/*
	 * D5 (Oct 2026, audit 1.16): the checker also covers the common catches
	 * a socket match misses. Each extra check is skipped when its inputs are
	 * left on "Not sure" / blank, so the old four checks still work alone.
	 *  - BIOS: which chipset generations need an update for which CPUs
	 *    (AMD and Intel CPU support lists: Ryzen 5000 on 300/400-series AM4,
	 *    Ryzen 9000 on 600-series AM5, 13th/14th Gen on 600-series LGA 1700).
	 *  - Board size vs case, cooler height vs case, and the GPU's power plug
	 *    vs the PSU's cables (12V-2x6, see psuCableAdvice()).
	 */
	var CHIPSET_OPTIONS = [
		{ value: 'skip', label: 'Not sure / skip this check' },
		{ value: 'AM4-300', label: 'AMD A320 / B350 / X370 (AM4, 300-series)' },
		{ value: 'AM4-400', label: 'AMD B450 / X470 (AM4, 400-series)' },
		{ value: 'AM4-500', label: 'AMD A520 / B550 / X570 (AM4, 500-series)' },
		{ value: 'AM5-600', label: 'AMD A620 / B650 / X670 (AM5, 600-series)' },
		{ value: 'AM5-800', label: 'AMD B840 / B850 / X870 (AM5, 800-series)' },
		{ value: 'LGA1851-800', label: 'Intel H810 / B860 / Z890 (LGA 1851, 800-series)' },
		{ value: 'LGA1700-600', label: 'Intel H610 / B660 / H670 / Z690 (LGA 1700, 600-series)' },
		{ value: 'LGA1700-700', label: 'Intel B760 / H770 / Z790 (LGA 1700, 700-series)' },
		{ value: 'LGA1200-400', label: 'Intel H410 / B460 / H470 / Z490 (LGA 1200, 400-series)' },
		{ value: 'LGA1200-500', label: 'Intel H510 / B560 / H570 / Z590 (LGA 1200, 500-series)' }
	];
	var FORM_RANK = { itx: 1, matx: 2, atx: 3, eatx: 4 };
	var FORM_NAMES = { itx: 'Mini-ITX', matx: 'Micro-ATX', atx: 'ATX', eatx: 'E-ATX' };

	// CPU family from the model name: { maker, gen }. AMD gen = the series
	// digit (3 = Ryzen 3000, 5, 7, 9); Intel gen = 12/13/14, or 'ultra'.
	function cpuFamily( cpu ) {
		var n = cpu ? cpu.name : '';
		var m = /Ryzen \d (\d)\d{3}/.exec( n );
		if ( m ) {
			return { maker: 'AMD', gen: +m[ 1 ] };
		}
		m = /i\d-(1[2-4])\d{3}/.exec( n );
		if ( m ) {
			return { maker: 'Intel', gen: +m[ 1 ] };
		}
		return /Core Ultra/.test( n ) ? { maker: 'Intel', gen: 'ultra' } : null;
	}

	// Returns [ status, text ]: true = fine, 'warn' = works but needs a step,
	// false = will not work. null when there is nothing to say.
	function biosCheck( cpu, socket, chipset ) {
		var fam = cpuFamily( cpu );
		if ( ! fam ) {
			return null;
		}
		var series = chipset === 'skip' ? '' : chipset.split( '-' )[ 1 ];
		var flashback = ' Many boards can update the BIOS without a CPU installed (BIOS Flashback); otherwise it needs an older supported CPU first.';
		if ( 'AM4' === socket ) {
			var name = 5 === fam.gen ? 'Ryzen 5000' : 'Ryzen ' + fam.gen + '000';
			if ( '300' === series ) {
				return [ 'warn', 'Needs a BIOS update, and not every 300-series board got ' + name + ' support (many A320 boards didn’t). Find this CPU on the board’s CPU support list before buying.' + flashback ];
			}
			if ( '400' === series ) {
				return [ 'warn', 'Needs a BIOS update that adds ' + name + ' support; check the board’s CPU support list for the version.' + flashback ];
			}
			if ( '500' === series ) {
				return 5 === fam.gen ? [ 'warn', 'Supported, but a board made before this CPU launched needs a BIOS update first; check the board’s CPU support list.' + flashback ] : [ true, 'Supported.' ];
			}
			return [ 'warn', 'Works on AM4 boards, but a 300- or 400-series board (A320, B350, X370, B450, X470) needs a BIOS update for ' + name + ', and an older 500-series board may too. Pick the chipset above for a specific answer.' ];
		}
		if ( 'AM5' === socket ) {
			if ( 9 === fam.gen ) {
				if ( '600' === series ) {
					return [ 'warn', 'Needs a BIOS from mid-2024 or later (Ryzen 9000 support); a board built before that needs an update first.' + flashback ];
				}
				return '800' === series ? [ true, 'Supported out of the box.' ] : [ 'warn', '800-series boards (B850, X870) support it out of the box; a 600-series board (A620, B650, X670) needs a BIOS from mid-2024 or later. Pick the chipset above for a specific answer.' ];
			}
			return [ true, 'Supported on every AM5 board. Update to the latest BIOS anyway for memory and stability fixes.' ];
		}
		if ( 'LGA1700' === socket ) {
			var micro = ' Intel recommends a BIOS with the 0x12B microcode or newer for 13th and 14th Gen desktop CPUs (the fix for the instability issue).';
			if ( 12 === fam.gen ) {
				return [ true, 'Supported on 600- and 700-series boards.' ];
			}
			if ( '600' === series ) {
				return [ 'warn', 'Needs a BIOS update for ' + fam.gen + 'th Gen; without it the board won’t start with this CPU.' + flashback + micro ];
			}
			if ( '700' === series ) {
				return 14 === fam.gen ? [ 'warn', 'Supported, but a board made before 14th Gen launched (October 2023) needs a BIOS update first.' + micro ] : [ 'warn', 'Supported.' + micro ];
			}
			return [ 'warn', 'A 700-series board (B760, Z790) supports it; a 600-series board (H610, B660, Z690) needs a BIOS update first. Pick the chipset above for a specific answer.' + micro ];
		}
		if ( 'LGA1851' === socket ) {
			return [ true, 'Supported on every LGA 1851 (800-series) board.' ];
		}
		return null;
	}

	// D5 (audit 1.17): one rule for the PSU Calculator and this checker.
	// Every RTX 4080/4090/5080/5090 uses the 16-pin 12V-2x6 plug (12VHPWR on
	// early RTX 40 cards); RTX 4070/5070-class cards vary by model (the
	// Founders Editions and many partner cards use it), and the rest mostly
	// use 8-pin PCIe.
	function gpuPlug( gpu ) {
		if ( ! gpu || gpu.brand !== 'NVIDIA' ) {
			return '';
		}
		if ( /RTX (4080|4090|5080|5090)/.test( gpu.name ) ) {
			return '16pin';
		}
		return /RTX [45]070/.test( gpu.name ) ? 'maybe' : '';
	}
	function psuCableAdvice( gpu ) {
		var plug = gpuPlug( gpu );
		if ( '16pin' === plug ) {
			return 'An ATX 3.1 PSU with a native 12V-2x6 (16-pin) cable: every ' + gpu.name + ' uses that plug. A native cable avoids the bulky adapter, and ATX 3.x units are built to ride out the card’s brief power spikes. Push the plug in fully until it clicks.';
		}
		if ( 'maybe' === plug ) {
			return 'An ATX 3.1 PSU with a native 12V-2x6 (16-pin) cable is the safe pick: many ' + gpu.name + ' models use that plug, while others use 8-pin PCIe. Check your exact card’s spec page.';
		}
		if ( gpu && gpu.tdp_w <= 75 ) {
			return 'Many cards at this power level draw everything from the motherboard slot and need no PSU cable; if yours has a 6- or 8-pin plug, make sure the PSU has a cable for it.';
		}
		if ( gpu && gpu.tdp_w >= 300 ) {
			return 'An ATX 3.x PSU is worth it for a card this power-hungry, since it is built for brief GPU power spikes. Most of these cards use 8-pin PCIe plugs (some use the 16-pin 12V-2x6): count them on your card’s spec page and make sure the PSU has a separate cable for each.';
		}
		return 'Most of these cards use 8-pin PCIe plugs: count them on your card’s spec page and make sure the PSU has a separate cable for each. ATX 3.x is fine but not required.';
	}
	function psuGrade( watts ) {
		return watts >= 850 ? '80+ Gold' : '80+ Bronze';
	}
	CS_DEFS.compatibility = {
		fields: [
			{ id: 'cpu', type: 'select', label: 'CPU', icon: 'cpu', default: HW.cpu && HW.cpu[ 'ryzen-5-7600' ] ? 'ryzen-5-7600' : 'custom', options: pickerWithOther( 'cpu', 'Other CPU — pick its socket below' ) },
			{ id: 'cpuSocket', type: 'select', label: 'Other CPU: socket (only used with "Other CPU")', default: 'AM5', options: SOCKET_OPTIONS },
			{ id: 'moboSocket', type: 'select', label: 'Motherboard socket', default: 'AM5', options: SOCKET_OPTIONS },
			{ id: 'ramType', type: 'select', label: 'RAM type you have/want', default: 'DDR5', options: [ { value: 'DDR4', label: 'DDR4' }, { value: 'DDR5', label: 'DDR5' } ] },
			{ id: 'moboRam', type: 'select', label: 'Motherboard supported RAM', default: 'DDR5', options: [ { value: 'DDR4', label: 'DDR4' }, { value: 'DDR5', label: 'DDR5' } ] },
			{ id: 'chipset', type: 'select', label: 'Motherboard chipset (for the BIOS check)', default: 'skip', options: CHIPSET_OPTIONS, help: 'The chipset is in the board\'s name, e.g. "B650" in "B650 Tomahawk".' },
			{ id: 'moboForm', type: 'select', label: 'Motherboard size', default: 'skip', options: [ { value: 'skip', label: 'Not sure / skip this check' }, { value: 'atx', label: 'ATX' }, { value: 'matx', label: 'Micro-ATX' }, { value: 'itx', label: 'Mini-ITX' }, { value: 'eatx', label: 'E-ATX' } ] },
			{ id: 'caseForm', type: 'select', label: 'Largest motherboard the case takes', default: 'skip', options: [ { value: 'skip', label: 'Not sure / skip this check' }, { value: 'atx', label: 'ATX (most mid towers)' }, { value: 'matx', label: 'Micro-ATX' }, { value: 'itx', label: 'Mini-ITX only' }, { value: 'eatx', label: 'E-ATX (most full towers)' } ] },
			{ id: 'coolerHeight', type: 'number', label: 'CPU air cooler height (mm, optional)', min: 20, max: 250, required: false, blankValue: 0, placeholder: 'e.g. 155', help: 'Leave blank for an AIO liquid cooler or to skip this check.' },
			{ id: 'caseCoolerMax', type: 'number', label: 'Case max CPU cooler height (mm, optional)', min: 20, max: 250, required: false, blankValue: 0, placeholder: 'e.g. 165' },
			{ id: 'gpu', type: 'select', label: 'GPU', icon: 'gpu', default: HW.gpu && HW.gpu[ 'rtx-4070' ] ? 'rtx-4070' : 'custom', options: pickerWithOther( 'gpu', 'Other GPU — enter system power below' ) },
			// U1 (U-09): start blank and skip the check when either is empty;
			// prefilled 320 / 380 mm made every result say "Fits".
			{ id: 'gpuLength', type: 'number', label: 'GPU length (mm, optional, check your exact model)', min: 100, max: 450, required: false, blankValue: 0, placeholder: 'e.g. 320', help: 'Length differs between brands of the same GPU, so take it from your card\'s spec page. Leave blank to skip this check.' },
			{ id: 'caseMaxLength', type: 'number', label: 'Case max GPU length (mm, optional)', min: 100, max: 600, required: false, blankValue: 0, placeholder: 'e.g. 380' },
			{ id: 'gpuPlug', type: 'select', label: 'GPU power plug', default: 'skip', options: [ { value: 'skip', label: 'Not sure / skip this check' }, { value: '8pin', label: '8-pin PCIe' }, { value: '16pin', label: '16-pin 12V-2x6 / 12VHPWR' } ], help: 'Every RTX 4080, 4090, 5080 and 5090 uses the 16-pin plug, and many RTX 4070- and 5070-class cards do; most other cards use 8-pin. Check your exact model.' },
			{ id: 'psuWattage', type: 'number', label: 'PSU wattage (optional)', min: 150, max: 1600, required: false, blankValue: 0, placeholder: 'e.g. 750', help: 'Leave blank to see the recommended wattage without checking a PSU.' },
			{ id: 'psuCable', type: 'select', label: 'PSU has a native 12V-2x6 (16-pin) cable', default: 'skip', options: [ { value: 'skip', label: 'Not sure / skip this check' }, { value: 'yes', label: 'Yes (ATX 3.0 / 3.1 PSU)' }, { value: 'no', label: 'No, 8-pin PCIe cables only' } ] },
			{ id: 'systemNeed', type: 'number', label: 'Other CPU/GPU: estimated system power need in watts (only used if either is "Other")', default: 450, min: 50, max: 1500 }
		],
		compute: function ( v, hw ) {
			var cpu = v.cpu !== 'custom' && hw.cpu ? hw.cpu[ v.cpu ] : null;
			var gpu = v.gpu !== 'custom' && hw.gpu ? hw.gpu[ v.gpu ] : null;
			var cpuSocket = cpu ? String( cpu.socket ).replace( /\s+/g, '' ) : v.cpuSocket;
			var cpuLabel = cpu ? cpu.name + ' (' + cpuSocket + ')' : cpuSocket + ' CPU';
			var checks = [];
			checks.push( [ 'CPU socket vs motherboard socket', cpuSocket === v.moboSocket, cpuSocket === v.moboSocket ? 'Match (' + cpuSocket + ')' : 'Mismatch — the ' + cpuLabel + ' will not fit ' + ( /^A/.test( v.moboSocket ) ? 'an ' : 'a ' ) + v.moboSocket + ' motherboard.' ] );
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
			if ( v.chipset !== 'skip' && v.chipset.split( '-' )[ 0 ] !== v.moboSocket ) {
				checks.push( [ 'Motherboard chipset vs socket', false, 'Mismatch — that chipset is for ' + v.chipset.split( '-' )[ 0 ] + ' boards, not ' + v.moboSocket + '. Check the board\'s spec page.' ] );
			} else if ( cpuSocket === v.moboSocket ) {
				var bios = biosCheck( cpu, cpuSocket, v.chipset );
				if ( bios ) {
					checks.push( [ 'CPU support / BIOS version', bios[ 0 ], bios[ 1 ] ] );
				}
			}
			if ( v.moboForm !== 'skip' && v.caseForm !== 'skip' ) {
				var formOk = FORM_RANK[ v.moboForm ] <= FORM_RANK[ v.caseForm ];
				checks.push( [ 'Motherboard size vs case', formOk, formOk ? 'Fits (' + FORM_NAMES[ v.moboForm ] + ' board in a case that takes up to ' + FORM_NAMES[ v.caseForm ] + ')' : 'Mismatch — ' + ( /^[AE]/.test( FORM_NAMES[ v.moboForm ] ) ? 'an ' : 'a ' ) + FORM_NAMES[ v.moboForm ] + ' board is too big for a case that takes up to ' + FORM_NAMES[ v.caseForm ] + '.' ] );
			}
			if ( v.coolerHeight > 0 && v.caseCoolerMax > 0 ) {
				var coolerOk = v.coolerHeight <= v.caseCoolerMax;
				checks.push( [ 'CPU cooler height vs case', coolerOk, coolerOk ? 'Fits (' + ( v.caseCoolerMax - v.coolerHeight ) + 'mm to spare)' : 'Too tall by ' + ( v.coolerHeight - v.caseCoolerMax ) + 'mm — pick a lower cooler or an AIO liquid cooler.' ] );
			}
			if ( v.gpuLength > 0 && v.caseMaxLength > 0 ) {
				checks.push( [ 'GPU length vs case clearance', v.gpuLength <= v.caseMaxLength, v.gpuLength <= v.caseMaxLength ? 'Fits (' + ( v.caseMaxLength - v.gpuLength ) + 'mm clearance)' : 'Too long by ' + ( v.gpuLength - v.caseMaxLength ) + 'mm.' ] );
			}

			// Same sum as the PSU Calculator with its default extras
			// (2 RAM sticks, 2 drives, 3 fans, 50W board), no overclock.
			var need = cpu && gpu ? ( cpu.max_power_w || cpu.tdp_w ) + gpu.tdp_w + 10 + 12 + 9 + 50 : v.systemNeed;
			var tally = Math.ceil( ( need * 1.3 ) / 50 ) * 50;
			var vendor = gpu && gpu.recommended_psu_w ? gpu.recommended_psu_w : 0;
			var recommended = Math.max( tally, vendor );
			var basis = ( cpu && gpu ? 'about ' + Math.round( need ) + 'W estimated load' : v.systemNeed + 'W system need you entered' ) + ' + 30% headroom' + ( vendor ? ', and ' + gpu.brand + '’s own ' + vendor + 'W recommendation for the ' + gpu.name : '' );
			var skipped = [];
			if ( v.psuWattage > 0 ) {
				var psuOk = v.psuWattage >= recommended;
				checks.push( [ 'PSU wattage vs recommended', psuOk, ( psuOk ? 'Sufficient — ' + v.psuWattage + 'W meets the ' + recommended + 'W recommended' : 'Too low — get at least ' + recommended + 'W' ) + ' (' + basis + ').' ] );
			} else {
				checks.push( [ 'Recommended PSU wattage', true, 'At least ' + recommended + 'W (' + basis + '). Enter your PSU’s wattage above to check it.' ] );
			}
			if ( ! ( v.gpuLength > 0 && v.caseMaxLength > 0 ) ) {
				skipped.push( 'GPU length vs case (enter both lengths to check it)' );
			}

			// 16-pin plug: from the user's answer, or known for every RTX
			// 4080/4090/5080/5090 (gpuPlug()).
			var plug = v.gpuPlug !== 'skip' ? v.gpuPlug : ( '16pin' === gpuPlug( gpu ) ? '16pin' : 'skip' );
			if ( '16pin' === plug ) {
				if ( 'yes' === v.psuCable ) {
					checks.push( [ 'GPU 16-pin plug vs PSU cables', true, 'Native 12V-2x6 cable — push the plug in fully until it clicks, with no sharp bend right at the plug.' ] );
				} else {
					checks.push( [ 'GPU 16-pin plug vs PSU cables', 'warn', ( 'no' === v.psuCable ? 'No native cable, so use' : 'This card takes the 16-pin 12V-2x6 plug. Without a native cable on the PSU, use' ) + ' the adapter that came with the card, with every one of its 8-pin plugs on its own PSU cable. An ATX 3.1 PSU with a native 12V-2x6 cable is the tidier and safer option.' ] );
				}
			} else if ( '8pin' === plug ) {
				checks.push( [ 'GPU power plugs vs PSU cables', true, '8-pin PCIe — count the plugs on your card\'s spec page and make sure the PSU has a separate PCIe cable for each.' ] );
			} else if ( 'maybe' === gpuPlug( gpu ) ) {
				checks.push( [ 'GPU power plugs vs PSU cables', 'warn', 'Many ' + gpu.name + ' models use the 16-pin 12V-2x6 plug, others use 8-pin PCIe. Pick your card\'s plug above to check it against the PSU.' ] );
			}

			var failed = checks.some( function ( c ) { return c[ 1 ] === false; } );
			var notes = checks.some( function ( c ) { return c[ 1 ] === 'warn'; } );
			var rows = checks.map( function ( c ) { return [ c[ 0 ], ( c[ 1 ] === 'warn' ? '⚠ ' : '' ) + c[ 2 ] ]; } );
			var headline = failed ? '<strong>⚠ One or more compatibility issues found — see below.</strong>' : ( notes ? '<strong>✅ These components fit together</strong>, with a step or two to check below.' : '<strong>✅ These components look compatible.</strong>' );
			if ( skipped.length ) {
				rows.push( [ 'Not checked', escapeHtml( skipped.join( '; ' ) ) ] );
			}
			return resultBlock( headline, rows, 'only the checks listed above are made; checks left on "Not sure" or blank are skipped. Also confirm radiator space for an AIO, RAM height under a large air cooler, and the motherboard’s power connectors on each manufacturer’s spec page.' );
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
			// D5 (Oct 2026, audit 1.21): 1080p medium was 4GB; 2026 games
			// commonly list 6-8GB as their minimum, so the floor is 6GB at
			// every 1080p setting. Keep in sync with BN_VRAM_NEED above,
			// cs_bottleneck_vram_need() and the combo VRAM FAQ.
			var base = { '1080p': { medium: 6, high: 6, ultra: 8 }, '1440p': { medium: 6, high: 8, ultra: 10 }, '4k': { medium: 8, high: 12, ultra: 14 } };
			var needed = base[ v.resolution ][ v.quality ];
			// U1 (Oct 2026, U-08): the minimum is what these settings need today.
			// Ray tracing and "hold up for 3+ years" used to be added to the
			// minimum too (4K Ultra + both = "at least 18GB", so a 16GB RTX 5080
			// "failed"); they now raise only the recommended figure, 2GB each.
			var extra = ( v.rayTracing ? 2 : 0 ) + ( v.futureProof ? 2 : 0 );
			// Oct 2026: the comfortable figure follows the site's VRAM guides
			// (about 8GB at 1080p, 12GB at 1440p, 16GB at 4K, the guides'
			// ray-tracing ranges included), never below the minimum plus the
			// extras picked, rounded up to a VRAM size cards actually ship with.
			// inc/bottleneck-content.php uses the same numbers for the
			// combo-page VRAM FAQ.
			var comfyBase = { '1080p': 8, '1440p': 12, '4k': 16 }[ v.resolution ];
			var comfy = Math.max( needed + extra, comfyBase );
			comfy = [ 8, 10, 12, 16, 20, 24, 32 ].find( function ( g ) { return g >= comfy; } ) || comfy;
			var gpusWith = function ( gb ) {
				return Object.keys( hw.gpu ).filter( function ( k ) { return hw.gpu[ k ].vram_gb >= gb; } )
					.sort( function ( a, b ) { return hw.gpu[ a ].vram_gb - hw.gpu[ b ].vram_gb; } )
					.slice( 0, 5 )
					.map( function ( k ) { return /GB\)$/.test( hw.gpu[ k ].name ) ? hw.gpu[ k ].name : hw.gpu[ k ].name + ' (' + hw.gpu[ k ].vram_gb + 'GB)'; } );
			};
			var why = [ v.rayTracing ? 'ray tracing' : '', v.futureProof ? '3+ years of newer games' : '' ].filter( Boolean ).join( ' and ' );
			var rows = [ [ 'Minimum VRAM (these settings today)', needed + ' GB' ] ];
			if ( v.rayTracing ) {
				rows.push( [ 'Recommended with ray tracing', ( needed + 2 ) + ' GB or more' ] );
			}
			if ( v.futureProof ) {
				rows.push( [ 'Recommended to hold up for 3+ years' + ( v.rayTracing ? ' (with ray tracing)' : '' ), ( needed + extra ) + ' GB or more' ] );
			}
			rows.push( [ 'Recommended VRAM (comfortable headroom' + ( why ? ', incl. ' + why : '' ) + ')', comfy + ' GB' ] );
			var matches = gpusWith( needed );
			if ( matches.length ) {
				rows.push( [ 'Cards with enough VRAM for the minimum (speed not checked)', matches.join( ', ' ) ] );
			}
			var comfyMatches = gpusWith( comfy );
			if ( comfy > needed && comfyMatches.length && comfyMatches.join() !== matches.join() ) {
				rows.push( [ 'Cards with the recommended VRAM (speed not checked)', comfyMatches.join( ', ' ) ] );
			}
			return resultBlock( 'These settings need at least <strong>' + needed + 'GB</strong> of VRAM today' + ( comfy > needed ? '; <strong>' + comfy + 'GB</strong> is recommended for comfortable headroom' + ( why ? ' with ' + why : '' ) + '.' : ', which also gives comfortable headroom.' ), rows, 'based on typical VRAM use at these settings, not a measurement of a specific game. High-resolution texture packs and mods can use more. This checks memory only: a card can have enough VRAM and still be too slow for these settings, so check its frame rate in the <a href="/fps-calculator/">FPS Calculator</a>.' );
		}
	};

	/* ---- 10. CPU vs GPU Calculator ---- */
	CS_DEFS['cpu-vs-gpu'] = {
		fields: [
			{ id: 'cpu', type: 'select-hardware', source: 'cpu', other: true, label: 'CPU', icon: 'cpu' },
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', other: true, label: 'GPU', icon: 'gpu' }
		],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ], gpu = hw.gpu[ v.gpu ];
			var all = bottleneckEstimate( cpu, gpu );
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
				headline = c === 'balanced' ? 'This pair is well matched at every resolution.' : ( c === 'cpu' ? 'The CPU is the bottleneck at every resolution.' : 'The GPU sets the frame rate at every resolution, which is normal for gaming.' );
			}
			return resultBlock( 'For this pair: ' + headline + ' See the resolution breakdown below.', rows, 'a general estimate for a typical AAA game at high settings, not a per-game benchmark. A CPU bottleneck % is the share of the GPU left idle; GPU-limited is normal and shows the CPU’s headroom; ' + SCORE_NOTE );
		}
	};

	/* ---- 11. CPU Performance Calculator ---- */
	// U1 (U-10): gaming tier from the score. Mirror: cs_tl_cpu_label().
	function cpuGamingTier( score ) {
		return score >= 80 ? 'High-end' : score >= 70 ? 'Upper mid-range' : score >= 60 ? 'Mid-range' : 'Budget';
	}
	CS_DEFS['cpu-performance'] = {
		fields: [ { id: 'cpu', type: 'select-hardware', source: 'cpu', label: 'CPU' } ],
		compute: function ( v, hw ) {
			var cpu = hw.cpu[ v.cpu ];
			// U1 (Oct 2026, U-10, Ali's decision): scores unchanged; the labels
			// follow where a chip sits for gamers today (Ryzen 5 5600 / Core
			// i5-12400F budget, Ryzen 5 7600 mid-range, 7800X3D / 9800X3D class
			// high-end), and the score is shown as a share of the fastest gaming
			// CPU instead of "/ 95", which read as a perfect score. Same lines
			// as cs_tl_cpu_label() and the CPU tier list (inc/tier-list.php).
			var tier = cpuGamingTier( cpu.score );
			var topKey = Object.keys( hw.cpu ).sort( function ( a, b ) { return hw.cpu[ b ].score - hw.cpu[ a ].score; } )[ 0 ];
			var share = Math.round( cpu.score / hwScoreMax( hw.cpu ) * 100 );
			var rows = [
				[ 'Gaming performance', share + '% of the fastest gaming CPU in our list' + ( topKey !== v.cpu ? ' (the ' + escapeHtml( hw.cpu[ topKey ].name ) + ')' : ' (this is it)' ) ],
				[ 'Gaming tier', tier ],
				[ 'Gaming score (our index)', fmt( cpu.score, 0 ) ],
				[ 'Cores / Threads', cpu.cores + ' / ' + cpu.threads ], [ 'Base / Boost clock', cpu.base_clock_ghz + ' GHz / ' + cpu.boost_clock_ghz + ' GHz' ],
				[ 'TDP', cpu.tdp_w + 'W' ], [ 'Socket', cpu.socket ]
			];
			return resultBlock( escapeHtml( cpu.name ) + ': gaming tier <strong>' + tier.toLowerCase() + '</strong>, about <strong>' + share + '%</strong> of the fastest gaming CPU we list.', rows, 'the tier and percentage are for games; heavily multi-threaded work such as video export can rank CPUs differently. ' + SCORE_NOTE.charAt( 0 ).toUpperCase() + SCORE_NOTE.slice( 1 ) );
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
			return resultBlock( gpu.name + ' is rated as <strong>' + gpu.tier + '</strong>.', rows, 'ray tracing, VRAM limits and per-game optimization can change how cards compare; ' + SCORE_NOTE );
		}
	};

	/* ---- 13. PC Cooling Calculator ---- */
	CS_DEFS.cooling = {
		fields: [
			{ id: 'gpu', type: 'select-hardware', source: 'gpu', label: 'GPU', icon: 'gpu' }
		].concat( cpuPickerFields( 'Other CPU: max turbo/boost power in watts', 'Intel lists this as "Maximum Turbo Power"; for AMD Ryzen use the PPT figure (about TDP × 1.35).' ) ).concat( [
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
			// D5 (Oct 2026, audit 1.20): case fans scale with the combined
			// heat load (under 350W / 350-549W / 550W+), not the case size
			// alone; a 575W GPU + 253W CPU used to get the same "3-5 fans" as
			// an RTX 4060 build.
			var heat = totalTdp < 350 ? 0 : totalTdp < 550 ? 1 : 2;
			var caseFans = {
				compact: [ '2 fans (1 intake, 1 exhaust) plus the GPU’s own fans', '3 fans with a mesh front or side panel; prioritise intake', '3 or more fans in a mesh case. This much heat is hard to move out of a small case, so a mid tower is the easier choice' ],
				mid: [ '3 fans (2 intake, 1 exhaust)', '4–5 fans (3 intake, 1–2 exhaust)', '5–6 fans (3 front intake, 2–3 top/rear exhaust) and a mesh front panel' ],
				full: [ '3–4 fans (2 intake, 1–2 exhaust)', '5 fans (3 intake, 2 exhaust)', '6–7 fans (3–4 intake, 3 exhaust)' ]
			}[ v.caseSize ][ heat ];
			var rows = [
				[ 'Combined CPU+GPU heat load', fmt( totalTdp, 0 ) + 'W' ],
				[ 'CPU heat used (' + cpu.name + ', max turbo/boost power)', cpu.max + 'W' ],
				[ 'GPU heat used (' + gpu.name + ', rated board power)', gpu.tdp_w + 'W' ],
				[ 'Recommended CPU cooler', cpuCooler ],
				[ 'Recommended case fan setup', caseFans + ( gpu.tdp_w >= 300 ? '. A card this power-hungry dumps most of its heat inside the case, so strong front intake matters most.' : '' ) ]
			];
			return resultBlock( 'Based on a combined heat load of <strong>' + fmt( totalTdp, 0 ) + 'W</strong>, here is what we recommend:', rows, 'sized from the CPU’s max turbo/boost power and the GPU’s rated TDP; fan counts step up at about 350W and 550W of combined heat. Case airflow, room temperature and how quiet you want the PC change what is enough.' );
		}
	};

	/* ---- 14 & 15. CPU / GPU Power Consumption Calculators ---- */
	function powerConsumptionDef( source, defaultKeyPrefix ) {
		return {
			fields: [
				{ id: 'part', type: 'select-hardware', source: source, label: source === 'cpu' ? 'CPU' : 'GPU' },
				{ id: 'load', type: 'select', label: 'Typical load', default: 'gaming', options: [
					{ value: 'idle', label: 'Idle / light use (~15%)' }, { value: 'gaming', label: 'cpu' === source ? 'Gaming / normal use' : 'Gaming / normal use (~70%)' }, { value: 'full', label: 'Full load / benchmark (~100%+ boost)' }
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
				} else if ( 'gaming' === v.load && 'cpu' === source ) {
					// D5 (audit 1.19): same gaming figure as the PC Power
					// Consumption Calculator.
					watts = cpuGamingWatts( part );
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
				return resultBlock( 'At ' + v.load + ' load, the ' + part.name + ' draws roughly <strong>' + fmt( watts, 0 ) + 'W</strong>.', rows, 'cpu' === source ? 'idle is about 15% of the rated TDP; gaming is about 55% of the manufacturer’s max turbo/boost power (45% for X3D chips), and full load uses that max power. Real draw depends on board power limits, cooling and the game or workload.' : 'idle and gaming are shares of the rated board power, and full load adds about 8% for boost spikes. Real draw depends on the card model, power limits and the game.' );
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
				[ 'Rated endurance', fmt( v.tbw, 2 ) + ' TBW' ],
				[ 'Estimated lifespan at this write rate', fmt( years, 1 ) + ' years' ]
			];
			return resultBlock( 'At ' + fmt( v.dailyWrites, 2 ) + 'GB/day, this SSD is rated to last roughly <strong>' + fmt( years, 1 ) + ' years</strong>.', rows, 'TBW is a warranty floor, not a hard cutoff — most SSDs keep working well beyond their rated TBW.' );
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
			// E4 (FUN-08): the echoed amount gets separators too.
			return resultBlock( fmtStorage( v.amount ) + ' ' + name( v.from ) + ' = <strong>' + fmtStorage( result ) + ' ' + name( v.to ) + '</strong>', rows, false );
		}
	};

	/* ---- 19 & 20 & 24. Download / Upload / File Transfer Time ---- */
	function transferTimeDef( label ) {
		return {
			fields: [
				{ id: 'size', type: 'number', label: 'File size', default: 5, min: 0.001, max: 1000000 },
				{ id: 'sizeUnit', type: 'select', label: 'Size unit', default: 'GB', options: [ { value: 'MB', label: 'MB' }, { value: 'GB', label: 'GB' }, { value: 'TB', label: 'TB' } ] },
				// U1 (U-52): no min clamp, so 0 is an error instead of "925d".
				{ id: 'speed', type: 'number', label: label + ' speed', default: 100, max: 100000 },
				{ id: 'speedUnit', type: 'select', label: 'Speed unit', default: 'Mbps', options: [ { value: 'Mbps', label: 'Mbps (megabits/sec)' }, { value: 'MBps', label: 'MB/s (megabytes/sec)' }, { value: 'Gbps', label: 'Gbps' } ] }
			],
			compute: function ( v ) {
				if ( ! ( v.speed > 0 ) ) {
					throw { msg: 'Enter a ' + label.toLowerCase() + ' speed above 0.' };
				}
				var sizeMB = v.size * ( { MB: 1, GB: 1000, TB: 1000000 }[ v.sizeUnit ] );
				var speedMBs = v.speedUnit === 'Mbps' ? v.speed / 8 : v.speedUnit === 'Gbps' ? ( v.speed * 1000 ) / 8 : v.speed;
				var seconds = sizeMB / speedMBs;
				// U1 (U-52): the headline is the realistic range (real speed
				// 10-20% below the rated speed), the full-speed time is the best case.
				var range = secondsToHuman( seconds / 0.9 ) + ' – ' + secondsToHuman( seconds / 0.8 );
				var rows = [ [ 'Realistic time (speed 10–20% below rated)', range ], [ 'Best case at the full rated speed', secondsToHuman( seconds ) ], [ 'Rated speed in MB/s', fmt( speedMBs, 2 ) + ' MB/s' ] ];
				return resultBlock( 'Estimated ' + label.toLowerCase() + ' time: about <strong>' + range + '</strong> (' + secondsToHuman( seconds ) + ' at the full rated speed)', rows, 'real-world transfer speed is usually 10–20% below your rated connection speed due to protocol overhead, Wi-Fi and server load, so the range uses 80–90% of it.' );
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
			{ id: 'streamingHours', type: 'number', label: 'Video streaming (hours/day)', default: 2, min: 0, max: 24, required: false, blankValue: 0 },
			{ id: 'streamingQuality', type: 'select', label: 'Streaming quality', default: 'hd', options: [ { value: 'sd', label: 'SD' }, { value: 'hd', label: 'HD' }, { value: '4k', label: '4K' } ] },
			{ id: 'browsingHours', type: 'number', label: 'Browsing / social media (hours/day)', default: 2, min: 0, max: 24, required: false, blankValue: 0 },
			{ id: 'gamingHours', type: 'number', label: 'Online gaming (hours/day)', default: 1, min: 0, max: 24, required: false, blankValue: 0 },
			{ id: 'callHours', type: 'number', label: 'Video calls (hours/day)', default: 0.5, min: 0, max: 24, required: false, blankValue: 0 }
		],
		compute: function ( v ) {
			// GB per hour. Matches Netflix's own published help-center figures
			// (SD ~1GB/hr, HD ~3GB/hr, 4K ~7GB/hr) and independent measured
			// testing (SD 0.2-0.35, HD 2.8-3.3, 4K 6.5-11.5 GB/hr) — verified
			// during the Sep 2026 audit against the old figures (0.3/1.0/2.5),
			// which understated HD and 4K usage by roughly 3x.
			// Oct 2026: SD was 0.3 (Netflix's "Low" data-saver tier, not SD).
			// Netflix's help page (help.netflix.com/en/node/87, checked Oct
			// 2026) lists up to 1GB/h SD, 3GB/h HD, 7GB/h 4K. Video calls use
			// 1GB/h: a 720p HD call at Zoom's published ~1.2 Mbps each way is
			// about 1.1GB/h, matching the page's worked example.
			// E4 (FUN-02): a cleared field counts as 0 hours (blankValue), not
			// its default, so an all-blank form is an error, not 201.6 GB.
			if ( v.streamingHours + v.browsingHours + v.gamingHours + v.callHours <= 0 ) {
				throw { msg: 'Enter the hours per day for at least one activity.' };
			}
			var streamRate = { sd: 1.0, hd: 3.0, '4k': 7.0 }[ v.streamingQuality ];
			var dailyGb = ( v.streamingHours * streamRate ) + ( v.browsingHours * 0.06 ) + ( v.gamingHours * 0.1 ) + ( v.callHours * 1.0 );
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
