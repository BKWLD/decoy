define(function (require) {
  
	// Dependencies
	var $ = require('jquery')
		, _ = require('lodash')
		, Backbone = require('backbone')
		, tooltip = require('admin/vendor/bootstrap/js/tooltip')
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

		// Events
		this.$icon.on('click', this.onClick);

	};

	// Create an Element editable icon
	View.create = function() {
		return new Icon(this.el, {

			// We're going to open them only via API
			trigger: 'manual',

			// Replace template with our own
			template: '<span class="decoy-element-icon glyphicon glyphicon-map-marker"></span>',
			
			// Don't add the Bootstrap animation class to it
			animation: false
		});
	};

	// Open editor
	View.onClick = function() {
		console.log('as');
	};
	
	// Return view class
	return Backbone.View.extend(View);
});