define(function (require) {
	
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
		, tooltip = require('admin/vendor/bootstrap/js/tooltip')
		, $doc = $(document)
		, editor_pad = 2 // Editor padding + borders 
		, icon_tpl = '<span class="decoy-el-icon"><span class="decoy-el-mask"></span></span>'
		, spinner_tpl = '<span class="glyphicon glyphicon-refresh decoy-el-spinner">'
		, icon_size = 20 // The initial size of the icon, both width and height
		, tween_length = 200 // How long the tween lasts
	;

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

	// Setup view
	var View = {};
	View.initialize = function() {
		_.bindAll(this);

		// Render the element icon 
		this.icon = this.create();
		this.$icon = this.icon.tip();
		this.icon.show();
		this.$icon.addClass('decoy-el-init');

		// Cache
		this.open = false;
		this.$mask = this.$icon.find('.decoy-el-mask');
		this.key = this.$el.data('decoy-el');

		// Events
		this.$icon.on('click', this.load);
		window.addEventListener('message', this.onPostMessage, false);

	};

	// Create an Element editable icon
	View.create = function() {
		return new Icon(this.el);
	};

	// Load the editor
	View.load = function(e) {
		e.stopPropagation(); // Prevent the outside click from handling it

		// Disable double clicks
		if (this.open) return;
		this.open = true;

		// Reset the value
		this.value = undefined;

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
		this.$spinner = $(spinner_tpl).appendTo(this.$icon);
	};

	// Handle iframe messages
	View.onPostMessage = function(e) {

		// Reject messages for other icons
		if (e.data.key != this.key) return;

		// Delegate different types of messages
		switch (e.data.type) {
			case 'height': return this.reveal(e.data.value + editor_pad);
			case 'saving': return this.saving(e.data.value);
			case 'close': return this.close();
		}
	};

	// Reveal the editor
	View.reveal = function(height) {

		// Remove the spinnner
		this.$spinner.remove();

		// Resize and reposition elements
		var iframe_width = this.$iframe.width();
		this.$icon.addClass('decoy-el-open')
		this.$iframe.css({ height: height }).addClass('decoy-el-show');
		this.$mask.css({ width: iframe_width, height: height });
		this.reposition(iframe_width, height);

		// Close the editor when the iframe submission is complete
		this.$iframe.on('load', this.close);
	};

	// Re apply position using inerhitted code
	View.reposition = function(w, h) {
		this.icon.applyPlacement(
			this.icon.getCalculatedOffset(this.icon.placement, this.icon.getPosition(), w, h), 
		this.icon.placement);
	};

	// Put the editor in a pending state because the user has submitted
	// the iframe form.  Also, preserve the value of the element for 
	// replacing in the frontend DOM
	View.saving = function(value) {
		this.value = value;
		this.$iframe.addClass('decoy-el-disable');
		this.spin();
	};

	// Close on click outside of the editor
	View.closeIfOutside = function(e) {
		if (!this.$icon.is(e.target) && !this.$icon.has(e.target).length) {
			this.close();
		}
	};

	// Close the editor
	View.close = function(e) {

		// Update the DOM
		if (this.value != undefined) this.updateDOM(this.value);

		// Resize and reposition elements back to close state
		this.$icon.removeClass('decoy-el-open');
		this.$iframe.removeClass('decoy-el-show');
		this.$mask.css({ width: '', height: ''});
		this.reposition(icon_size, icon_size);

		// Remove the iframe and spinner (if it's still out there) from DOM
		this.$iframe.off('load', this.close);
		_.delay(function(self) { self.$iframe.remove(); }, tween_length, this);
		this.$spinner.remove();

		// Remove mouse listeners
		$doc.off('click', this.closeIfOutside);

		// Allow opening again
		this.open = false;
	};

	// Live update the DOM with the change the user made
	View.updateDOM = function(value) {

		// If an image tag, put the value in the source
		if (this.$el.is('img')) this.$el.attr('src', value);

		// If this is an "a" tag and the key looks like a link, put it in href
		else if (this.$el.is('a') && /(link|url)$/.test(this.key)) this.$el.attr('href', value);

		// If the element has a style tag with a background and the key looks like 
		// an image, set it as the background image
		else if (this.$el.is('[style*="background"]') && /(image|background|marquee)$/.test(this.key))
			this.$el.css('background-image', 'url("'+value+'")');

		// Otherwise, the default behavior is to replace the text of the el
		else this.$el.text(value);

	};
	
	// Return view class
	return Backbone.View.extend(View);
});