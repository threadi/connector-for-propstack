/**
 * Submit the sorting of the list of objects as soon as the visitor chooses another one.
 *
 * Without JavaScript, the button of the form is used.
 *
 * @package
 */
( function () {
	'use strict';

	/**
	 * Initialize the sorting forms in the given element (or the whole document).
	 *
	 * Could be called again for content, which is loaded later, e.g. a list updated via AJAX.
	 *
	 * @param {Element|Document} root The element to search in.
	 */
	function initSorting( root ) {
		const element =
			root && typeof root.querySelectorAll === 'function' ? root : document;

		// get the forms in the element, incl. the element itself.
		const forms = Array.prototype.slice.call(
			element.querySelectorAll( 'form.cfprop-sorting' )
		);
		if ( typeof element.matches === 'function' && element.matches( 'form.cfprop-sorting' ) ) {
			forms.push( element );
		}

		forms.forEach( function ( form ) {
			// bail if this form is already initialized.
			if ( form.classList.contains( 'cfprop-sorting-js' ) ) {
				return;
			}

			const select = form.querySelector( 'select' );
			if ( ! select ) {
				return;
			}

			// hide the button, it is not needed with JavaScript.
			form.classList.add( 'cfprop-sorting-js' );

			// submit the form on change.
			select.addEventListener( 'change', function () {
				form.submit();
			} );
		} );
	}

	// make the initialization available for content loaded later.
	window.cfpropInitSorting = initSorting;

	// initialize the sortings in content, which is updated later (e.g., by the AJAX filter of Connector for Propstack Pro).
	document.addEventListener( 'cfprop-archive-updated', function ( event ) {
		initSorting( event.target );
	} );

	// initialize the sortings in the actual page.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initSorting( document );
		} );
	} else {
		initSorting( document );
	}
} )();
