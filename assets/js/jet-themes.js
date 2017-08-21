( function( $, settings ) {

	'use strict';

	function jetThemesInit() {

		var themeModel,
			themesView,
			themesCollection,
			filterModel,
			filtersView,
			filtersCollection,
			itemView,
			moreView,
			jetThemes,
			themes,
			filtersData,
			activeFilters = {},
			filtersQuery = {};

		itemView = Backbone.View.extend({

			initialize: function(options) {
				this.template = options.template;
				this.className = options.className;
			},

			render: function() {
				this.$el.html( this.template( this.model.attributes ) );
				return this;
			}
		});

		themesView = Backbone.View.extend({

			className: 'themes-list',

			render: function() {
				this.collection.each( function( theme ) {
					var currentView = new itemView( {
						model: theme,
						template: wp.template( 'themes-item' ),
						className: 'theme-item'
					} );
					this.$el.append( currentView.render().el );
				}, this );

				return this;
			}
		});

		filtersView = Backbone.View.extend({

			className: 'theme-filters',

			render: function() {

				this.$el.append( '<span class="theme-filters__back">&lt; Back</span>' );

				this.collection.each( function( filter ) {
					var currentView = new itemView( {
						model: filter,
						template: wp.template( 'filters' ),
						className: 'theme-filter'
					} );
					this.$el.append( currentView.render().el );
				}, this );

				return this;
			}
		});

		moreView = Backbone.View.extend({

			template: wp.template( 'more' ),

			render: function() {
				this.$el.html( this.template() );
				return this;
			}
		});

		themeModel = wp.api.models.Post.extend({
			urlRoot: wpApiSettings.root + wpApiSettings.versionString + settings.slug,
			defaults: {
				type: settings.slug
			}
		});

		filterModel = wp.api.WPApiBaseModel.extend({
			urlRoot: wpApiSettings.root + settings.filtersRoute
		});


		themesCollection = wp.api.collections.Posts.extend({
			url: wpApiSettings.root + wpApiSettings.versionString + settings.slug,
			model: themeModel
		});

		filtersCollection = wp.api.WPApiBaseCollection.extend({
			url: wpApiSettings.root + settings.filtersRoute,
			model: filterModel
		});

		jetThemes = {

			init: function() {

				var more = new moreView();

				filtersData = new filtersCollection();
				themes      = new themesCollection();

				jetThemes.updateCollection();
				jetThemes.getFilters();

				$( '.more-wrap' ).html( more.render().el );

				$( document ).on( 'click', '.theme-more', jetThemes.handleMore );
				$( document ).on( 'click', '.active-filters__item', jetThemes.removeFilter );
				$( document ).on( 'click', '.theme-filter__item.active-filter', jetThemes.removeFilter );
				$( document ).on( 'click', '.theme-filter__item:not(.active-filter)', jetThemes.handleFilter );
				$( document ).on( 'click', '.theme-filter__label', jetThemes.handleFiltersNav );
				$( document ).on( 'click', '.filters-mobile-trigger', jetThemes.switchNavPlane );
				$( document ).on( 'click', '.theme-filters__back', jetThemes.switchNavPlane );
				$( document ).on( 'click', jetThemes.closeFiltersNav );
				$( window ).on( 'resize', jetThemes.setNavClass );

				jetThemes.setNavClass();

			},

			getFilters: function(){
				filtersData.fetch().done( function() {
					var filters = new filtersView( { collection: filtersData } );
					$( '.filters-wrap' ).append( filters.render().el );
				} );
			},

			setNavClass: function() {

				var width      = $( document ).width(),
					breakpoint = parseInt( settings.mobileBreakpoint );

				if ( width < breakpoint ) {
					$( '.filters-wrap:not(.filters-mobile)' ).addClass( 'filters-mobile' );
				} else {
					$( '.filters-wrap.filters-mobile' ).removeClass( 'filters-mobile' );
				}

			},

			switchNavPlane: function() {
				if ( $( '.theme-filters.visible-filters' ).length ) {
					$( '.theme-filters.visible-filters' ).removeClass( 'visible-filters' );
				} else {
					$( '.theme-filters' ).addClass( 'visible-filters' );
				}
			},

			handleFilter: function( event ) {

				var $this = $( this ),
					tax   = $this.data( 'tax' ),
					term  = $this.data( 'term' );

				event.preventDefault();
				event.stopPropagation();

				if ( $this.hasClass( 'active-filter' ) ) {
					return;
				}

				$this.closest( '.theme-filter__terms' ).removeClass( 'visible-filter' ).prev().removeClass( 'is-active' );
				$this.addClass( 'active-filter' );

				if ( _.isEmpty( activeFilters ) ) {
					activeFilters = {};
				}

				activeFilters[ 'term_' + term ] = {
					term: term,
					tax: tax,
					label: $this.text()
				}

				if ( ! filtersQuery[ tax ] ) {
					filtersQuery[ tax ] = [];
				}

				if ( 0 > $.inArray( term, filtersQuery[ tax ] ) ) {
					filtersQuery[ tax ].push( term );
				}

				jetThemes.updateActiveFilters();
				jetThemes.updateCollection( filtersQuery );

			},

			removeFilter: function( event ) {

				var $this = $( this ),
					tax   = $this.data( 'tax' ),
					term  = $this.data( 'term' );

				event.preventDefault();

				delete activeFilters[ 'term_' + term ];

				filtersQuery[ tax ] = _( filtersQuery[ tax ] ).filter( function( item ) {
					return item != term;
				} );

				$( '.theme-filter__item.active-filter[data-term="' + term + '"]' ).removeClass( 'active-filter' );

				jetThemes.updateActiveFilters();
				jetThemes.updateCollection( filtersQuery );

			},

			updateActiveFilters: function() {

				var $filters = $( '.active-filters' ),
					template = wp.template( 'active-filters' );

				if ( ! $filters.length ) {
					return;
				}

				$filters.html( template({
					title: settings.activeFiltersTitle,
					activeFilters: activeFilters,
				}) );

			},

			handleMore: function( event ) {

				if ( themes.hasMore() ) {
					themes.more().done( jetThemes.fetchThemes );
				} else {
					$( this ).addClass( 'hidden' );
				}
			},

			updateCollection: function( data ) {

				if ( ! data ) {
					data = {};
				}

				data.per_page = settings.perPage;
				themes.fetch( { data: data } ).done( jetThemes.fetchThemes );

			},

			fetchThemes: function( data ) {

				var mainView = new themesView( { collection: themes } );

				if ( themes.state.currentPage && 1 < themes.state.currentPage ) {
					$( '.themes-wrap' ).append( mainView.render().el );
				} else {
					$( '.themes-wrap' ).html( mainView.render().el );
				}

				if ( 1 < themes.state.totalPages && themes.state.currentPage < themes.state.totalPages ) {
					$( '.theme-more.hidden' ).removeClass( 'hidden' );
				} else {
					$( '.theme-more:not(.hidden)' ).addClass( 'hidden' );
				}
			},

			handleFiltersNav: function( event ) {

				var $trigger   = $( this ),
					$nav       = $trigger.next(),
					$container = $trigger.parents( '.theme-filter' );

				event.stopPropagation();

				if ( $trigger.hasClass( 'is-active' ) ) {
					$trigger.removeClass( 'is-active' );
					$nav.removeClass( 'visible-filter' );
				} else {
					$container.siblings().find( '.is-active' ).each( function() {
						$( this ).removeClass( 'is-active' );
						$( this ).next().removeClass( 'visible-filter' );
					} );
					$nav.addClass( 'visible-filter' );
					$trigger.addClass( 'is-active' );
				}

			},

			closeFiltersNav: function( event ) {

				$( '.theme-filter__label.is-active' ).each( function() {
					$( this ).removeClass( 'is-active' ).next().removeClass( 'visible-filter' );
				} );

			}

		};

		jetThemes.init();

	}

	wp.api.loadPromise.then( jetThemesInit );

}( jQuery, jetThemesSettings ) );
