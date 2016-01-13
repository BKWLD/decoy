// --------------------------------------------------
// Stuff for the login screen
// --------------------------------------------------
define(function (require) {

	// Dependencies
	var $ = require('jquery')
		, Backbone = require('backbone')
		, _ = require('underscore')
		, $win = $(window)
		, LoginAnimation = require('../animation/decoy-anim')
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
		this.$('h1').addClass('show');
		_.delay(_.bind(function() {
			this.$('h4').addClass('show');
		}, this), 1000);


		//Add decoy login animation on screens greater than screen-sm
		if( $win.width() >= 768 ) {

			// If using hot module reloading, wait an arbitrary amount to styles have
			// been loaded.  I couldn't figure out a way to know when the styles were
			// injected https://github.com/webpack/style-loader/issues/83
			if (module && module.hot) _.delay(this.start, 100);
			else this.start();
		}

	};

	// Init the animation
	View.start = function() {
		new LoginAnimation({
			el: '#main',
			color: rgb2hex($('body').css('background-color')),
			size: 25,
			spawnRate: 1,
			cellRate: 1,
			cellBrightness: 1,
			colorRange: 0.5,
			flashSpeedIn: 0.04,
			flashSpeedOut: 0.02
		});
	};

	// Fix the size of the branding piece
	View.resize = function() {
		this.$branding.css('top', (this.$form.outerHeight() - this.$branding.height())/2 );
	};

	// Utility function for converting rgb() values to hexes
	// http://stackoverflow.com/a/3627747
	function rgb2hex(rgb) {
		if (/^#[0-9A-F]{6}$/i.test(rgb)) return rgb;
		rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
		function hex(x) { return ("0" + parseInt(x).toString(16)).slice(-2); }
		return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
	}

	// Return it
	return Backbone.View.extend(View);

});
