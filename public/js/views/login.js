// --------------------------------------------------
// Stuff for the login screen
// --------------------------------------------------
define(function (require) {
	
	// Dependencies
	var $ = require('jquery')
		, Backbone = require('backbone')
		, _ = require('underscore')
		, $win = $(window)
	;
	
	// Create view
	var View = {};
	
	// Constructor
	View.initialize = function() {
		_.bindAll(this);

		// Cache
		this.$branding = $('.branding');
		this.$form = $('.form');
		this.$container = $('.max-width');

		// Listen for resizes and adjust the size of the branding container
		this.resize();
		$win.on('orientationchange resize load', _.debounce(this.resize, 100));

		// Animate in in the subtitle
		_.delay(_.bind(function() {
			this.$('h4').addClass('show');
		}, this), 1000);

	};

	// Fix the size of the branding piece
	View.resize = function() {
		this.$branding.width(this.$container.width() - this.$form.outerWidth());
		this.$branding.css('top', (this.$form.outerHeight() - this.$branding.height())/2 );

	};

	// Return it
	return Backbone.View.extend(View);

});