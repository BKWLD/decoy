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

		// Define router class 
		this.router = new (Backbone.Router.extend());

		// Begin routing
		Backbone.history.start({
			pushState: true,
			root: window.location.href.match(/\/admin\/[^\/]+/)[0],
			silent: true
		});

		// Register click listeners
		this.$('a').on('click', this.updateRoute);

	};

	View.updateRoute = function(e) {
		var cur = $(e.currentTarget);
		this.router.navigate(cur.data('slug'), {trigger: false});
		this.page = cur.data('slug');
	};

	// Return it
	return Backbone.View.extend(View);

});