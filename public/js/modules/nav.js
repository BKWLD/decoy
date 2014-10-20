// This class handles the state of the navigation when 
// you go down to mobile widths, so it can toggle on and off
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
		this.$mainnav = this.$('.main-nav');

		// events
		this.$grabber.on('click', this.openNav);
		this.$close.on('click', this.closeNav);
		
		// for each top-level nav, toggle the active state
		this.$mainnav.find('.top-level').on('click', this.toggleSubnav);

	};

	View.openNav = function() {
		this.$el.addClass('show');
	};

	View.closeNav = function() {
		this.$el.removeClass('show');
	};

	// open and close the subnav drawers
	View.toggleSubnav = function(e) {
		var cur = $(e.currentTarget).parent();

		if(cur.hasClass('active')) cur.removeClass('active');
		else cur.addClass('active');
	};
	
	// Return the view
	return Backbone.View.extend(View);
});
