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
		, editor_width = 300 // How wide to make the revealed state
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
		return new Icon(this.el, {

			// We're going to open them only via API
			trigger: 'manual',

			// Replace template with our own
			template: icon_tpl,
			
			// Don't add the Bootstrap animation class to it
			animation: false
		});
	};

	// Load the editor
	View.load = function(e) {
		e.stopPropagation(); // Prevent the outside click from handling it

		// Disable double clicks
		if (this.open) return;
		this.open = true;

		// Close on any click outside of it
		$doc.on('click', this.closeIfOutside);
		
		// Show loading progress
		this.$spinner = $(spinner_tpl).appendTo(this.$icon);		

		// Build an iframe that will render the element field editor
		this.$iframe = $('<iframe>').appendTo(this.$mask).attr({
			src: '/admin/elements/field/'+this.key
		});

	};

	// Handle iframe messages
	View.onPostMessage = function(e) {

		// Reject messages for other icons
		if (e.data.key != this.key) return;

		// Delegate different types of messages
		switch (e.data.type) {
			case 'height': return this.reveal(e.data.value + editor_pad);
			case 'close': return this.close();
		}
	}

	// Reveal the editor
	View.reveal = function(height) {

		// Remove the spinnner
		this.$spinner.remove();

		// Size the iframe and animate it in
		this.$iframe.css({ height: height }).addClass('display');

		// Open up the iframe's container
		this.$icon.addClass('decoy-el-open')
		this.$mask.css({
				width: editor_width,
				height: height,
				marginLeft: -editor_width/2,
				marginTop: -height/2
			})
		;
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
		this.$mask.css({
			width: '',
			height: '',
			marginLeft: '',
			marginTop: ''
		});

		// Remove the iframe and loader
		this.$iframe.removeClass('display');
		_.delay(function(self) { self.$iframe.remove(); }, tween_length, this);
		this.$spinner.remove();

		// Remove mouse listeners
		$doc.off('click', this.closeIfOutside);

		// Allow opening again
		this.open = false;
	};
	
	// Return view class
	return Backbone.View.extend(View);
});