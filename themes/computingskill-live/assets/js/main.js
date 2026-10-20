/**
 * Site-wide JS: mobile nav toggle and table scrollers. Calculator logic lives
 * in calculators.js and is enqueued only on pages that need it.
 */
(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var toggle = document.querySelector( '.cs-menu-toggle' );
		var nav = document.getElementById( 'cs-primary-nav' );
		var masthead = document.getElementById( 'cs-masthead' );

		// U4 (U-19): anchors and keyboard focus stop below the sticky header
		// (html scroll-padding-top in fixes.css reads this). Measured with the
		// menu closed, since the open menu is part of the header.
		var setHeaderOffset = function () {
			if ( ! masthead || ( nav && nav.classList.contains( 'is-open' ) ) ) {
				return;
			}
			var sticky = window.getComputedStyle( masthead ).position;
			var h = ( 'sticky' === sticky || 'fixed' === sticky ) ? masthead.getBoundingClientRect().height : 0;
			document.documentElement.style.setProperty( '--cs-header-offset', Math.round( h + 16 ) + 'px' );
		};
		setHeaderOffset();
		window.addEventListener( 'resize', setHeaderOffset );
		window.addEventListener( 'load', setHeaderOffset );

		if ( toggle && nav ) {
			var setOpen = function ( open ) {
				toggle.setAttribute( 'aria-expanded', String( open ) );
				nav.classList.toggle( 'is-open', open );
				setHeaderOffset();
			};
			var isOpen = function () {
				return toggle.getAttribute( 'aria-expanded' ) === 'true';
			};

			toggle.addEventListener( 'click', function () {
				setOpen( ! isOpen() );
			} );

			// E5 (UX-02): Escape and a click outside the header close the menu.
			document.addEventListener( 'keydown', function ( e ) {
				if ( ( e.key === 'Escape' || e.key === 'Esc' ) && isOpen() ) {
					setOpen( false );
					toggle.focus();
				}
			} );
			document.addEventListener( 'click', function ( e ) {
				var header = document.getElementById( 'cs-masthead' );
				if ( isOpen() && header && ! header.contains( e.target ) ) {
					setOpen( false );
				}
			} );
		}

		// E5 (A11Y-04): a table scroller that really scrolls sideways can be
		// reached with Tab and scrolled with the arrow keys, and is announced
		// with its table's caption. Wraps that fit stay out of the tab order.
		var labelFor = function ( wrap ) {
			var cap = wrap.querySelector( 'caption' );
			var text = cap ? cap.textContent : '';
			if ( ! text ) {
				var el = wrap.previousElementSibling;
				while ( el && ! /^H[2-4]$/.test( el.tagName ) ) {
					el = el.previousElementSibling;
				}
				text = el ? el.textContent : '';
			}
			text = text.replace( /\s+/g, ' ' ).trim();
			return text ? text + ' (table)' : 'Table';
		};
		var update = function () {
			var used = {};
			document.querySelectorAll( '.cs-table-wrap' ).forEach( function ( wrap ) {
				if ( wrap.scrollWidth > wrap.clientWidth + 1 ) {
					wrap.setAttribute( 'tabindex', '0' );
					wrap.setAttribute( 'role', 'region' );
					if ( ! wrap.hasAttribute( 'aria-label' ) ) {
						// U4: two tables under one heading (/cpu/) got the same
						// name; regions need different ones.
						var label = labelFor( wrap );
						used[ label ] = ( used[ label ] || 0 ) + 1;
						wrap.setAttribute( 'aria-label', used[ label ] > 1 ? label.replace( / \(table\)$/, '' ) + ' (table ' + used[ label ] + ')' : label );
					} else {
						used[ wrap.getAttribute( 'aria-label' ) ] = ( used[ wrap.getAttribute( 'aria-label' ) ] || 0 ) + 1;
					}
					wrap.classList.add( 'is-scrollable' );
				} else {
					wrap.removeAttribute( 'tabindex' );
					wrap.removeAttribute( 'role' );
					wrap.classList.remove( 'is-scrollable' );
				}
			} );
		};
		var timer;
		var later = function () {
			clearTimeout( timer );
			timer = setTimeout( update, 150 );
		};
		update();
		window.addEventListener( 'resize', later );
		window.addEventListener( 'load', update );
		// Calculator results (Can I Run It) add tables after a click.
		if ( window.MutationObserver ) {
			document.querySelectorAll( '.cs-calc-result' ).forEach( function ( box ) {
				new MutationObserver( later ).observe( box, { childList: true, subtree: true } );
			} );
		}
	} );
})();
