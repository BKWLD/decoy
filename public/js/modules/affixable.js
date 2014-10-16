/**
 * This module calculates the size of an affixable element so that it's width
 * doesn't change when it's fixed.  A number of fixable elements will be in %
 * width containers which will change the width when fixed.  In particular, this
 * was designed for the fragment's sidebar.
 *
 * Another feature of this module is dynamic calculation of the offset for the BS
 * affix plugin.
 */
define(function (require) {
  
	// Dependencies
	require('bootstrap');
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
		, $win = $(window)
	;

	// Setup view
	var View = {};
	View.initialize = function() {
		_.bindAll(this);
		
		// Cache
		this.fixed = false;
		this.top = 80 // How far down to place it while affixed

		// Listen to resizing to keep track of settings
		$win.on('orientationchange resize', _.throttle(this.onResize, 200));

		// Listen for the affixable to switch between fixed and static positioning 
		this.$el.on('affix.bs.affix', this.onFixing);
		this.$el.on('affix-top.bs.affix', this.onStatic);

		// Enable plugin
		this.measureLayout();
		this.enablePlugin();
	};

	// Cache the width and offset of the affixable so they can be applied when
	// it becomes affixed
	View.onResize = function() {

		// If fixed, re-calculate it's size
		if (this.fixed) {
			this.clearLayout();
			this.$el.css('position', 'relative');
			this.measureLayout();
			this.$el.css('position', '');
			this.setLayout();

		// Calculate sizes for when it later becomes fixed
		} else this.measureLayout();

		// Re-set affix plugin's offset
		this.enablePlugin();
	};

	// Enable affixing
	View.enablePlugin = function() {
		this.$el.affix({ offset: this.offset - this.top });
	};

	// Meaasure with the width and offset right before they get set  
	View.onFixing = function() {
		this.fixed = true;
		this.setLayout();
	};

	// Switch back to static positioning
	View.onStatic = function() {
		this.fixed = false;
		this.clearLayout();
	};

	// Store the dimensions
	View.measureLayout = function() {
		this.width = this.$el.width();
		this.offset = this.$el.offset().top;
	};

	// Set the dimenions of the fixable
	View.setLayout = function() {
		this.$el.css({
			width: this.width, 
			top: this.top
		});
	};

	// Clear the layout
	View.clearLayout = function() {
		this.$el.css({
			width: '', 
			top: '',
			position: ''
		});
	};
	
	// Return view class
	return Backbone.View.extend(View);
});