define(function (require) {
  
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
		, tooltip = require('admin/vendor/bootstrap/js/tooltip')
		, $doc = $(document)
		, editor_pad = 2 // Editor padding + borders 
	;

	// Get a reference to the Bootstrap Tooltip class
	var Tooltip = $.fn.tooltip.Constructor;

	// Subclass Tooltip to so methods can be overriden without affecting anything
	// else using Tooltipls for it's intended purpose
	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/create
	var Icon = function() { Tooltip.apply(this, arguments); };
	Icon.prototype = Object.create(Tooltip.prototype);
	Icon.prototype.constructor = Icon;

	// Bypass the check for content, Icon's don't have titles.
	Icon.prototype.hasContent = function() {
		return true;
	}

	// Setup view
	var View = {};
	View.initialize = function() {
		_.bindAll(this);

		// Render the element icon 
		this.icon = this.create();
		this.$icon = this.icon.tip();
		this.icon.show();
		this.$icon.addClass('decoy-el-init');

		// Cache some properties
		this.key = this.$el.data('decoy-el');
		this.closed = { 
			left: parseInt(this.$icon.css('left'), 10), 
			top: parseInt(this.$icon.css('top'), 10) 
		};

		// Events
		this.$icon.on('click', this.open);
		window.addEventListener('message', this.onPostMessage, false);

	};

	// Create an Element editable icon
	View.create = function() {
		return new Icon(this.el, {

			// We're going to open them only via API
			trigger: 'manual',

			// Replace template with our own
			template: '<span class="decoy-el-icon"></span>',
			
			// Don't add the Bootstrap animation class to it
			animation: false
		});
	};

	// Open editor
	View.open = function(e) {
		
		// Close on any click outside of it
		e.stopPropagation(); // Prevents the following from handlin
		$doc.on('click', this.closeIfOutside);
		
		// Get the initial width and height
		var size = this.openSize();

		// Open up the dimensions of the icon
		this.$icon.addClass('decoy-el-open');
		this.$icon.css({
			width: size.width,
			height: size.height,
			left: this.closed.left - size.width/2,
			top: this.closed.top - size.height/2
		});

		// Build an iframe that will render the element field editor
		this.$iframe = $('<iframe>').appendTo(this.$icon).attr({
			src: '/admin/elements/field/'+this.key
		});

	};

	// Handle iframe messages
	View.onPostMessage = function(e) {

		// Reject messages for other icons
		if (e.data.key != this.key) return;

		// The iframe has loaded and has a height
		if (e.data.type == 'height') {
			this.$iframe.addClass('loaded');
			this.$icon.css('height', e.data.value + editor_pad);
		}

		// Close the iframe
		if (e.data.type == 'close') this.close();
	}

	// Close on click outside of the editor
	View.closeIfOutside = function(e) {
		if (!this.$icon.is(e.target) && !this.$icon.has(e.target).length) {
			this.close();
		}
	};

	// Close the editor
	View.close = function(e) {

		// Change display back to close
		this.$icon.removeClass('decoy-el-open');
		this.$icon.css({
			width: '',
			height: '',
			left: this.closed.left,
			top: this.closed.top
		});

		// Remove the iframe
		this.$iframe.removeClass('loaded').delay(200).remove();

		// Remove mouse listeners
		$doc.off('click', this.closeIfOutside);
	};

	// Return the initial size once opened
	View.openSize = function() {
		return { width: 400, height: 200 }
	};
	
	// Return view class
	return Backbone.View.extend(View);
});