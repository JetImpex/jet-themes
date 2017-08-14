( function( $, settings ) {

	'use strict';

	var themeModel,
		themesCollection,
		themesView,
		themeView,
		filtersView,
		jetThemes,
		themes;



	themesView = Backbone.View.extend({

		tagName: 'div',
		className: 'themes-list',

		render: function() {
			this.collection.each( function( theme ) {
				var currentView = new themeView( { model: theme } );
				this.$el.append( currentView.render().el );
			}, this );

			return this;
		}
	});




	themeView = Backbone.View.extend({

		tagName: 'div',
		className: 'theme-item',
		template: wp.template( 'themes-item' ),

		render: function() {
			this.$el.html( this.template( this.model.attributes ) );
			return this;
		}
	});





	filtersView = Backbone.View.extend({

		template: wp.template( 'filters' ),

		render: function() {
			this.$el.html( this.template( this.model.attributes ) );
			return this;
		}
	});





	themeModel = wp.api.models.Post.extend({
		urlRoot: wpApiSettings.root + wpApiSettings.versionString + settings.slug,
		defaults: {
			type: settings.slug
		}
	});





	themesCollection = wp.api.collections.Posts.extend({
		url: wpApiSettings.root + wpApiSettings.versionString + settings.slug,
		model: themeModel
	});





	jetThemes = {

		init: function() {

			themes = new themesCollection();

			$( '.theme-filter__item' ).on( 'click', jetThemes.handleFilter );
			$( '.theme-more' ).on( 'click', jetThemes.handleMore );

			jetThemes.updateCollection();

		},

		handleFilter: function( event ) {

			jetThemes.updateCollection({
				'template-category': 408,
				'topic': 423
			});

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
		}

	};

	jetThemes.init();

}( jQuery, jetThemesSettings ) );
