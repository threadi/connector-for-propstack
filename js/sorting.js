/**
 * Submit the sorting of the list of objects as soon as the visitor chooses another one.
 *
 * Without JavaScript, the button of the form is used.
 *
 * @package
 */
document.addEventListener( 'DOMContentLoaded', function () {
	document
		.querySelectorAll( 'form.cfprop-sorting' )
		.forEach( function ( form ) {
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
} );
