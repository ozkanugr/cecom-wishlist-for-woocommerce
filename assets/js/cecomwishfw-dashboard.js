( function () {
	'use strict';
	var wrap = document.querySelector( '.cwfw-dashboard' );
	if ( ! wrap ) { return; }
	var links  = wrap.querySelectorAll( '[data-cwfw-subtab]' );
	var panels = wrap.querySelectorAll( '.cwfw-subpanel' );
	function activate( key, updateUrl ) {
		for ( var i = 0; i < panels.length; i++ ) {
			var p = panels[ i ];
			if ( p.getAttribute( 'data-cwfw-subpanel' ) === key ) {
				p.classList.remove( 'd-none' );
			} else {
				p.classList.add( 'd-none' );
			}
		}
		for ( var j = 0; j < links.length; j++ ) {
			var a  = links[ j ];
			var on = ( a.getAttribute( 'data-cwfw-subtab' ) === key );
			a.classList.toggle( 'active', on );
			a.setAttribute( 'aria-selected', on ? 'true' : 'false' );
		}
		if ( updateUrl ) {
			var u = new URL( window.location.href );
			u.searchParams.set( 'sub', key );
			window.history.pushState( {}, '', u.toString() );
		}
	}
	for ( var i = 0; i < links.length; i++ ) {
		links[ i ].addEventListener( 'click', function ( e ) {
			if ( e.ctrlKey || e.metaKey || e.shiftKey ) { return; }
			e.preventDefault();
			activate( this.getAttribute( 'data-cwfw-subtab' ), true );
		} );
	}
} )();
