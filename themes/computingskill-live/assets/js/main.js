/**
 * Site-wide JS: mobile nav toggle only. Calculator logic lives in
 * calculators.js and is enqueued only on pages that need it.
 */
(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var toggle = document.querySelector( '.cs-menu-toggle' );
		var nav = document.getElementById( 'cs-primary-nav' );

		if ( toggle && nav ) {
			toggle.addEventListener( 'click', function () {
				var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
				toggle.setAttribute( 'aria-expanded', String( ! expanded ) );
				nav.classList.toggle( 'is-open' );
			} );
		}
	} );
})();
