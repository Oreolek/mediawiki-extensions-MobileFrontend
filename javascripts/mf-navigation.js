/*global document, window, MobileFrontend, navigator, placeholder */
/*jslint sloppy: true, white:true, maxerr: 50, indent: 4, plusplus: true*/
MobileFrontend.navigation = (function() {
	var u = MobileFrontend.utils, mfePrefix = MobileFrontend.prefix,
		message = MobileFrontend.message;

	function toggleActionBar() {
		var menu = $( '#' + mfePrefix + 'nav' )[0];
		if( menu.style.display ) {
			menu.style.display = '';
		} else {
			menu.style.display = 'block';
		}
		window.location.hash = '#';
	}
	function closeOverlay( ) {
		$( '#' + mfePrefix + 'overlay' ).empty();
		$( 'html' ).removeClass( 'overlay' );
	}

	function createOverlay( heading, contents ) {
		var overlay = document.getElementById( mfePrefix + 'overlay' );
		$( 'html' ).addClass( 'overlay' );
		$( '<div class="header">' ).appendTo( '#' + mfePrefix + 'overlay' );
		$( '<button id="close"></button>' ).text( message( 'collapse-section' ) ).
			click( closeOverlay ).appendTo( '#' + mfePrefix + 'overlay' );
		$( '<h2>' ).text( heading ).appendTo( '#' + mfePrefix + 'overlay .header' );
		$( overlay ).append( contents );
		$( 'a', overlay.lastChild ).bind( 'click', function() {
			toggleActionBar();
		});
	}

	function createTableOfContents() {
		var ul = $( '<ul />' )[0], li, a,
			click = function() {
				var hash = this.getAttribute( 'href' );
				MobileFrontend.toggle.wm_reveal_for_hash( hash );
				if( hash ) {
					window.location.hash = hash;
				}
				closeOverlay();
			};
		$( '.section h2 span' ).each( function( i, el ) {
			li = $( '<li />' ).appendTo( ul )[0];
			a = $( '<a />' ).attr( 'href', '#' + $( el ).attr( 'id' ) ).
				text( $( el ).text() ).bind( 'click', click).appendTo( li );
		} );
		createOverlay( message( 'contents-heading' ), ul );
	}

	function createLanguagePage() {
		var ul = $( '<ul />' )[0], li, a;

		$( '#' + mfePrefix + 'language-selection option' ).each( function(i, el) {
			li = $( '<li />' ).appendTo( ul )[0];
			a = $( '<a />' ).attr( 'href', el.value ).text( $( el ).text() ).appendTo( li );
		} );
		createOverlay( message( 'language-heading' ), ul );
	}

	function init() {
		$( '<div id="' + mfePrefix + 'overlay"></div>' ).appendTo( document.body );
		var search = document.getElementById(  mfePrefix + 'search' );

		function toggleNavigation() {
			var doc = document.documentElement;
			if( !u( doc ).hasClass( 'navigationEnabled' ) ) {
				u( doc ).addClass( 'navigationEnabled' );
			} else {
				u( doc ).removeClass( 'navigationEnabled' );
			}
		}
		$( '#' + mfePrefix + 'main-menu-button' ).click( function( ev ) {
			toggleNavigation();
			ev.preventDefault();
			ev.stopPropagation();
		} );

		if( window.location.hash === '#mw-mf-page-left' ) {
			u( document.body ).addClass( 'noTransitions' );
			toggleNavigation();
			window.setTimeout( function() {
				u( document.body ).removeClass( 'noTransitions' );
			}, 1000 );
		}

		$( '#' + mfePrefix + 'page-menu-button' ).click( function( ev ) {
			ev.preventDefault();
			toggleActionBar();
		});

		$( '#' + mfePrefix + 'toc' ).click( createTableOfContents );
		$( '#' + mfePrefix + 'language' ).click( createLanguagePage );

		u( search ).bind( 'focus', function() {
			u( document.documentElement ).removeClass( 'navigationEnabled' );
		} );
	}
	if( typeof(jQuery) !== 'undefined' ) {
		init();
	}
}());
