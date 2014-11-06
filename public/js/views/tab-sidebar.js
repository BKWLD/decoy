// --------------------------------------------------
// Switch "pages" using push state on tad-sidebar layouts
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery')
		, Backbone = require('backbone')
		, _ = require('underscore')
		, $win = $(window)
	;
	
	// Create view
	var View = {
		page: null
	};
	
	// Constructor
	View.initialize = function() {
		_.bindAll(this);

		// Cache
		this.$nav = $('.nav').find('a');

		// Define router class 
		this.router = new (Backbone.Router.extend({
			routes: {
				':id': 'frag',
				'': 'default'
			},

			// Go through each a, and if there is a match then simulate that button click
			frag: function (id) {
				$('.nav').find('a').each(function(i, el) {
					if($(el).data('slug') == id) {
						$(el).tab('show');
						this.page = $(el).data('slug');
						return false;
					}
				});
			},

			// Select the first tab
			default: _.bind(function () {
				var slug = this.$nav.first().data('slug');
				this.router.navigate(slug);
				this.page = slug;
			}, this)
		}));

		// Begin routing
		Backbone.history.start({
			pushState: true,
			root: window.location.href.match(/\/admin\/[^\/]+/)[0]
		});

		// Register click listeners
		this.$nav.on('click', this.updateRoute);

	};

	View.updateRoute = function(e) {
		var cur = $(e.currentTarget);
		this.router.navigate(cur.data('slug'), {trigger: true});
		this.page = cur.data('slug');
	};

	// Return it
	return Backbone.View.extend(View);

});