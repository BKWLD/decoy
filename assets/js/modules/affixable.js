/**
 * This module dynamically calculates the offset of the element for the BS affix 
 * plugin.  You can also opt into it setting the width of the element when it's
 * affixed.  All is controlled via class and data attribtues:
 *
 * <div class="affixable" data-top="0" data-set-width="true"></div>
 *
 * Note: `padding-top` is used to do the offset rather than `top` because when the
 * element goes fixed, BS reads it's offset different, because it may actually
 * change because of the `top`.  On the otherhand, `padding-top` keeps the actual
 * offset of the element in the same place when fixed, but pushes the content
 * down to where you'd expect. This was something I observed on the Elements sidebar.
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
		this.auto_set_width = this.$el.data('set-width');
		this.height = this.$el.height();

		// How far down to place it while affixed
		this.top = this.$el.data('top') || 0;

		// Listen to resizing to keep track of settings
		$win.on('orientationchange resize', _.throttle(this.onResize, 200));

		// Listen for the affixable to switch between fixed and static positioning 
		this.$el.on('affix.bs.affix', this.onFixing);
		this.$el.on('affixed.bs.affix', this.onFixed);
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
		this.$el.affix({ offset: {top: this.offset - this.top }});
	};

	// Meaasure with the width and offset right before they get set  
	View.onFixing = function() {
		this.fixed = true;
		this.setLayout();
	};

	// If the affixing has been disabled, immediately remove affixing. Affix has
	// no API to remove itself, so this is the best method I could find.
	View.onFixed = function() {
		if (this.disabled) {
			this.$el.removeClass('affix affix-top affix-bottom').css('padding-top', '');
		}
	};

	// Switch back to static positioning
	View.onStatic = function() {
		this.fixed = false;
		this.clearLayout();
	};

	// Store the dimensions
	View.measureLayout = function() {
		if (this.auto_set_width) this.width = this.$el.outerWidth();
		this.offset = this.$el.offset().top;

		// Check if the element is too tall for the page.  The 84 comes from the
		// the actions bar
		this.disabled = this.height > $win.height() - this.top - 60;
	};

	// Set the dimenions of the fixable
	View.setLayout = function() {
		var css = { paddingTop: this.top };
		if (this.width) css.width = this.width;
		this.$el.css(css);
	};

	// Clear the layout
	View.clearLayout = function() {
		this.$el.css({
			width: '', 
			paddingTop: '',
			position: ''
		});
	};
	
	// Return view class
	return Backbone.View.extend(View);
});