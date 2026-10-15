( function () {
	'use strict';

	var i18n = window.qtfwCountdown || {};
	var nodes = document.querySelectorAll( '.qtfw-countdown[data-end]' );

	if ( ! nodes.length ) {
		return;
	}

	function pad( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	function format( seconds ) {
		var d = Math.floor( seconds / 86400 );
		var h = Math.floor( ( seconds % 86400 ) / 3600 );
		var m = Math.floor( ( seconds % 3600 ) / 60 );
		var s = seconds % 60;
		var out = '';

		if ( d > 0 ) {
			out += d + ( i18n.days || 'd' ) + ' ';
		}

		return out + pad( h ) + ( i18n.hours || 'h' ) + ' ' + pad( m ) + ( i18n.minutes || 'm' ) + ' ' + pad( s ) + ( i18n.seconds || 's' );
	}

	function tick() {
		var now = Math.floor( Date.now() / 1000 );

		nodes.forEach( function ( node ) {
			var end = parseInt( node.getAttribute( 'data-end' ), 10 );
			var time = node.querySelector( '.qtfw-countdown__time' );
			var left = end - now;

			if ( ! time ) {
				return;
			}

			if ( left <= 0 ) {
				node.classList.add( 'is-ended' );
				node.textContent = i18n.ended || '';
				return;
			}

			time.textContent = format( left );
		} );
	}

	tick();
	window.setInterval( tick, 1000 );
}() );
