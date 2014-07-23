// --------------------------------------------------
// Stuff for the Fragments view
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
		this.$nav = $('.fragments-nav').find('a');

		//define router class 
		this.router = new (Backbone.Router.extend({
			routes: {
				'admin/fragments/:id': 'frag',
				'admin/fragments': 'default'
			},

			frag: function (id) {

				// go through each a, and if there is a match then simulate that button click
				$('.fragments-nav').find('a').each(function(i, el) {
					if($(el).data('slug') == id) {
						$(el).tab('show');
						this.page = $(el).data('slug');
						return;
					}
				});
			},

			default: _.bind(function () {
				var slug = this.$nav.first().data('slug');
				this.router.navigate('/admin/fragments/' + slug);
				this.page = slug;
			}, this)x
		}));

		Backbone.history.start({pushState: true});

		// click on the nav a tags to update the route
		this.$nav.on('click', this.updateRoute);

	};

	View.updateRoute = function(e) {
		var cur = $(e.currentTarget);
		this.router.navigate('/admin/fragments/' + cur.data('slug'), {trigger: true});
		this.page = cur.data('slug');
	};

	// Return it
	return Backbone.View.extend(View);

});