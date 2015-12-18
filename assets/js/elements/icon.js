define(function (require) {
	
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
		, tooltip = require('admin/vendor/bootstrap/js/tooltip')
		, $doc = $(document)
		, $body = $('body')
		, editor_pad = 2 // Editor padding + borders 
		, icon_tpl = '<span class="decoy-el-icon"><span class="decoy-el-mask"></span><span class="glyphicon glyphicon-pencil"></span></span>'
		, highlight_tpl = '<div class="decoy-el-highlight"></div>'
		, icon_size = 20 // The initial size of the icon, both width and height
		, tween_length = 200 // How long the tween lasts
		, all = [] // Will contain all the icons on the page
	;

	// Reposition all elements on a window resize
	$(window).on('orientationchange resize', _.debounce(function() {
		_.each(all, function(icon) { 
			icon.reposition(null, null, true);
			icon.layoutHighlight();
		});
	}, 50));

	/**
	 * Subclass Bootstrap's Tooltip to leverage their placement logic
	 */

	// Get a reference to the Bootstrap Tooltip class
	var Tooltip = $.fn.tooltip.Constructor;

	// Subclass Tooltip to so methods can be overriden without affecting anything
	// else using Tooltipls for it's intended purpose
	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/create
	var Icon = function() { Tooltip.apply(this, arguments); };
	Icon.prototype = Object.create(Tooltip.prototype);
	Icon.prototype.constructor = Icon;

	// Tweak defaults
	Icon.prototype.getDefaults = function() {
		var defaults = Tooltip.DEFAULTS;
		defaults.placement = 'auto';
		defaults.animation = false; // Don't add the Bootstrap animation class
		defaults.template = icon_tpl; // Replace template with our own
		defaults.trigger = 'manual'; // We're going to open them only via API
		defaults.viewport = { selector: 'body', padding: 5 }; // Increase padding
		return defaults;
	};

	// Bypass the check for content, these icon's don't have titles.
	Icon.prototype.hasContent = function() {
		return true;
	};

	// Remember the initial placement
	Icon.prototype.applyPlacement = function(offset, placement) {
		if (!this.placement) this.placement = placement;
		Tooltip.prototype.applyPlacement.apply(this, arguments);
	};

	// Override to check both all sizes when placing  This should be the same as it's
	// counterpart in the parent class but missing the main if/else condition.  Also,
	// the subclassing allows the viewport restriction to be overriden when the icons
	// are initially placed.  We don't want icons for DOM elements that are below the 
	// fold to be kept within the viewport.
	Icon.prototype.getViewportAdjustedDelta = function (placement, pos, actualWidth, actualHeight) {
		var delta = { top: 0, left: 0 }
		if (!this.$viewport) return delta;

		// Don't apply viewport restrictions until the icon is shown.  At that point, it's used
		// to restrict the expanded view.
		if (!this.shown) return delta;

		var viewportPadding = this.options.viewport && this.options.viewport.padding || 0
		var viewportDimensions = this.getPosition(this.$viewport)

		// Top / bottom
		var topEdgeOffset    = pos.top - viewportPadding - viewportDimensions.scroll
		var bottomEdgeOffset = pos.top + viewportPadding - viewportDimensions.scroll + actualHeight
		if (topEdgeOffset < viewportDimensions.top) { // top overflow
			delta.top = viewportDimensions.top - topEdgeOffset
		} else if (bottomEdgeOffset > viewportDimensions.top + viewportDimensions.height) { // bottom overflow
			delta.top = viewportDimensions.top + viewportDimensions.height - bottomEdgeOffset
		}

		// Left / right
		var leftEdgeOffset  = pos.left - viewportPadding
		var rightEdgeOffset = pos.left + viewportPadding + actualWidth
		if (leftEdgeOffset < viewportDimensions.left) { // left overflow
			delta.left = viewportDimensions.left - leftEdgeOffset
		} else if (rightEdgeOffset > viewportDimensions.width) { // right overflow
			delta.left = viewportDimensions.left + viewportDimensions.width - rightEdgeOffset
		}

		return delta;
	}

	/**
	 * Define the custom icon and it's behavior
	 */

	// Setup view
	var View = {};
	View.initialize = function() {
		_.bindAll(this);

		// Render the element icon 
		this.icon = this.create();
		this.$icon = this.icon.tip();
		this.icon.show();
		this.icon.shown = true;
		this.$icon.addClass('decoy-el-pos-tween');

		// Cache
		this.open = false;
		this.$mask = this.$icon.find('.decoy-el-mask');
		this.key = this.$el.data('decoy-el');
		this.$glyph = this.$icon.find('.glyphicon');

		// Events
		this.$icon.on('click', this.load);
		this.$icon.on('mouseenter', this.over);
		this.$icon.on('mouseleave', this.out);
		window.addEventListener('message', this.onPostMessage, false);

		// Add to the collection
		all.push(this);
	};

	// Create an Element editable icon
	View.create = function() {
		return new Icon(this.el);
	};

	// Show a hightlight bounding box around the element when the icon
	// is hovered
	View.over = function(e) {
		if (this.$highlight) return;
		this.$highlight = $(highlight_tpl).appendTo($body);
		this.layoutHighlight();
	};

	// Size the highlight box
	View.layoutHighlight = function() {
		if (!this.$highlight) return;
		var pos = this.$el.offset();
		this.$highlight.css({
			top: pos.top,
			left: pos.left,
			width: this.$el.outerWidth(),
			height: this.$el.outerHeight(),
		});
	};
	
	// Remove the bounding box on mouseout but not when the dialog is open
	View.out = function(e) {
		if (!this.$highlight || this.open) return;
		this.$highlight = this.$highlight.addClass('decoy-el-hide')
		_.delay(function($highlight) { $highlight.remove(); }, tween_length, this.$highlight);
		this.$highlight = null;
	};

	// Load the editor
	View.load = function(e) {

		// Disable double clicks
		if (this.open) return;
		this.open = true;

		// Close on any click outside of it
		$doc.on('click', this.closeIfOutside);
				
		// Build an iframe that will render the element field editor
		this.spin();
		this.$iframe = $('<iframe>').appendTo(this.$mask).attr({
			src: '/admin/elements/field/'+this.key
		});

	};

	// Show the spinner
	View.spin = function() {
		this.$glyph.addClass('glyphicon-refresh').removeClass('glyphicon-pencil');
	};

	// Remove spinner
	View.stopSpin = function() {
		this.$glyph.addClass('glyphicon-pencil').removeClass('glyphicon-refresh');
	};

	// Handle iframe messages
	View.onPostMessage = function(e) {

		// Reject messages for other icons
		if (e.data.key != this.key) return;

		// Delegate different types of messages
		switch (e.data.type) {
			case 'height': return this.reveal(e.data.value + editor_pad);
			case 'saving': return this.saving();
			case 'saved': return this.saved(e.data.value);
			case 'close': return this.close();
		}
	};

	// Reveal the editor
	View.reveal = function(height) {

		// Remove the spinnner after transition is complete
		_.delay(this.stopSpin, tween_length);

		// Resize and reposition elements
		var iframe_width = this.$iframe.width();
		this.$icon.addClass('decoy-el-open decoy-el-show')
		this.$iframe.css({ height: height });
		this.$mask.css({ width: iframe_width, height: height });
		this.reposition(iframe_width, height);
	};

	// Re-apply position using inerhitted code
	View.reposition = function(w, h, immediate) {

		// If no width or height, use the last values
		if (!w) w = this.last_w;
		if (!h) h = this.last_h;
		this.last_w = w;
		this.last_h = h;

		// Don't tween the change in position and clear the viewport so bootstrap doesn't try
		// to keep the icons in the viewport.
		if (immediate) {
			this.$icon.removeClass('decoy-el-pos-tween');
			this.icon.$viewport = null;
		}

		// Calculate and apply to left and top
		this.icon.applyPlacement(
			this.icon.getCalculatedOffset(this.icon.placement, this.icon.getPosition(), w, h), 
		this.icon.placement);

		// Restore tweening
		if (immediate) {
			this.$icon.addClass('decoy-el-pos-tween');
			this.icon.$viewport = $body;
		}
	};

	// Put the editor in a pending state because the user has submitted
	// the iframe form. 
	View.saving = function() {
		this.$icon.removeClass('decoy-el-show');
		var size = 60;
		this.$mask.css({ height: size, width: size });
		this.reposition(size, size);
		this.spin();
	};

	// The iframe has finished saving, so update the DOM with the new value
	// and then close it
	View.saved = function(value) {
		this.updateDOM(value);
		this.close();
	}

	// Close on click outside of the editor
	View.closeIfOutside = function(e) {
		if (!this.$icon.is(e.target) && !this.$icon.has(e.target).length) {
			this.close();
		}
	};

	// Close the editor
	View.close = function(e) {

		// Allow opening again
		this.open = false;

		// Resize and reposition elements back to close state
		this.$icon.removeClass('decoy-el-open decoy-el-show');
		this.$mask.css({ width: '', height: ''});

		// Reposition all icons on the page
		_.each(all, function(icon) { 
			icon.reposition(icon_size, icon_size, icon.el != this.el); 
		}, this);

		// Remove the iframe and spinner (if it's still out there) from DOM
		this.$iframe.off('load', this.close);
		_.delay(function(self) { self.$iframe.remove(); }, tween_length, this);
		this.stopSpin();

		// Hide the higlight
		this.out();

		// Remove mouse listeners
		$doc.off('click', this.closeIfOutside);

	};

	// Live update the DOM with the change the user made
	View.updateDOM = function(value) {

		// If an image tag, put the value in the source
		if (this.$el.is('img')) {
			this.$el.attr('src', value);

			// When the image finishes loading, reposition again
			this.$el.on('load', _.bind(function() {
				_.each(all, function(icon) { 
					icon.reposition(null, null, icon.el != this.el); 
				}, this);
			}, this));
		}

		// If this is an "a" tag and the key looks like a link, put it in href
		else if (this.$el.is('a') && /(link|url|file|pdf)$/.test(this.key)) this.$el.attr('href', value);

		// If the element has a style tag with a background and the key looks like 
		// an image, set it as the background image
		else if (this.$el.is('[style*="background"]') && /(image|background|marquee)$/.test(this.key))
			this.$el.css('background-image', 'url("'+value+'")');

		// Otherwise, the default behavior is to replace the text of the el
		else this.$el.html(value);

	};
	
	// Return view class
	return Backbone.View.extend(View);
});