( function ( M, $ ) {

	var PageList,
		View = M.require( 'View' ),
		browser = M.require( 'browser' );

	/**
	 * List of items page view
	 * @class PageList
	 * @extends View
	 */
	PageList = View.extend( {
		/**
		 * @cfg {Object} defaults Default options hash.
		 * @cfg {Array} defaults.pages Array of page objects returned from the server.
		 * E.g. [
		 *   {
		 *     heading: "<strong>C</strong>laude Monet",
		 *     id: undefined,
		 *     listThumbStyleAttribute: "background-image: url(http://127.0.0.1:8080/images/thumb/thumb.jpg)",
		 *     pageimageClass: "list-thumb-y",
		 *     title: "Claude Monet",
		 *     url: "/wiki/Claude_Monet",
		 *     thumbnail: {
		 *       height: 62,
		 *       source: "http://127.0.0.1:8080/images/thumb/thumb.jpg",
		 *       width: 80
		 *     }
		 *   }
		 * ]
		 */
		defaults: {
			pages: []
		},
		/**
		 * Render page images for the existing page list. Assumes no page images have been loaded.
		 * Only load when wgImagesDisabled has not been activated via Special:MobileOptions.
		 *
		 * @method
		 */
		renderPageImages: function () {
			var self = this,
				pages = {},
				$ul = this.$( '.page-list' ),
				delay = browser.isWideScreen() ? 0 : 1000;

			if ( !mw.config.get( 'wgImagesDisabled' ) ) {
				window.setTimeout( function () {
					$.each( self.options.pages, function ( i, page ) {
						var thumb;
						if ( page.thumbnail ) {
							thumb = page.thumbnail;
							page.listThumbStyleAttribute = 'background-image: url(' + thumb.source + ')';
							page.pageimageClass = thumb.width > thumb.height ? 'list-thumb-y' : 'list-thumb-x';
						} else {
							page.pageimageClass = 'list-thumb-none list-thumb-x';
						}
						pages[page.title] = page;
					} );

					// Render page images
					$ul.find( 'li' ).each( function () {
						var $li = $( this ),
							title = $li.attr( 'title' ),
							page = pages[title];

						if ( page ) {
							$li.find( '.list-thumb' ).addClass( page.pageimageClass )
								.attr( 'style', page.listThumbStyleAttribute );
						}
					} );
				}, delay );
			}
		},
		/**
		 * @inheritdoc
		 */
		postRender: function () {
			this.renderPageImages();
		},
		template: mw.template.get( 'mobile.pagelist', 'PageList.hogan' ),
		templatePartials: {
			item: mw.template.get( 'mobile.pagelist', 'PageListItem.hogan' )
		}
	} );

	M.define( 'PageList', PageList );

}( mw.mobileFrontend, jQuery ) );