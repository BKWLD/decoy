// --------------------------------------------------
// Used in generic autocompletes and designed to be
// extended by other views that need extended feature
// --------------------------------------------------
define(function (require) {
	
	// dependencies
	var $ = require('jquery'),
		_ = require('underscore'),
		Backbone = require('backbone');
			
	// public view module
	var View = {
		isOpen: false
	};
		
	View.initialize = function(params) {
		_.bindAll(this);

		// Selectors
		this.$win = $(window);
		this.$grabber = $('.glyphicon-th-list');
		this.$close = this.$('.close');

		// events
		this.$grabber.on('click', this.openNav);
		this.$close.on('click', this.closeNav);

	};

	View.openNav = function() {
		this.$el.addClass('show');
	};

	View.closeNav = function() {
		this.$el.removeClass('show');
	};
	
	// Return the view
	return Backbone.View.extend(View);
});
