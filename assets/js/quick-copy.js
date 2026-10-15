( function () {
	'use strict';

	function fallbackCopy( text ) {
		var area = document.createElement( 'textarea' );
		area.value = text;
		area.setAttribute( 'readonly', '' );
		area.style.position = 'fixed';
		area.style.opacity = '0';
		document.body.appendChild( area );
		area.select();

		try {
			document.execCommand( 'copy' );
		} catch ( e ) {
			// Copy not supported; nothing more to do.
		}

		document.body.removeChild( area );
	}

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest ? event.target.closest( 'a.qtfw-copy' ) : null;

		if ( ! link ) {
			return;
		}

		event.preventDefault();

		var text = link.getAttribute( 'data-copy' ) || '';
		var original = link.textContent;

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).catch( function () {
				fallbackCopy( text );
			} );
		} else {
			fallbackCopy( text );
		}

		link.textContent = link.getAttribute( 'data-copied' ) || original;
		window.setTimeout( function () {
			link.textContent = original;
		}, 1200 );
	} );
}() );
